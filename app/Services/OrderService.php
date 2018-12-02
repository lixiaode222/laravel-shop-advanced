<?php

namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\CouponCode;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Exceptions\InternalException;
use Carbon\Carbon;
use App\Jobs\RefundInstallmentOrder;

class OrderService
{

    //普通商品的下单逻辑
    public function store(User $user, UserAddress $address, $remark, $items, CouponCode $coupon = null)
    {
        if ($coupon) {
            $coupon->checkAvailable($user);
        }
        // 开启一个数据库事务
        $order = \DB::transaction(function () use ($user, $address, $remark, $items, $coupon) {
            // 更新此地址的最后使用时间
            $address->update(['last_used_at' => Carbon::now()]);
            // 创建一个订单
            $order   = new Order([
                'address'      => [ // 将地址信息放入订单中
                    'address'       => $address->full_address,
                    'zip'           => $address->zip,
                    'contact_name'  => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark'       => $remark,
                'total_amount' => 0,
                'type'         => Order::TYPE_NORMAL,
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            $totalAmount = 0;
            // 遍历用户提交的 SKU
            foreach ($items as $data) {
                $sku  = ProductSku::find($data['sku_id']);
                // 创建一个 OrderItem 并直接与当前订单关联
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price'  => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }
            if ($coupon) {
                // 总金额已经计算出来了，检查是否符合优惠券规则
                $coupon->checkAvailable($user, $totalAmount);
                // 把订单金额修改为优惠后的金额
                $totalAmount = $coupon->getAdjustedPrice($totalAmount);
                // 将订单与优惠券关联
                $order->couponCode()->associate($coupon);
                // 增加优惠券的用量，需判断返回值
                if ($coupon->changeUsed() <= 0) {
                    throw new CouponCodeUnavailableException('该优惠券已被兑完');
                }
            }
            // 更新订单总金额
            $order->update(['total_amount' => $totalAmount]);
            
            // 将下单的商品从购物车中移除
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });

        // 这里我们直接使用 dispatch 函数
        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }

    //众筹商品的下单逻辑
    public function crowdfunding(User $user,UserAddress $address,ProductSku $sku,$amount){

        //开启事务
        $order = \DB::transaction(function () use ($user,$address,$sku,$amount){

            //更新地址的使用时间
            $address->update(['last_used_at' => Carbon::now()]);
            //创建一个订单
            $order = new Order([
                'address'      => [ // 将地址信息放入订单中
                    'address'       => $address->full_address,
                    'zip'           => $address->zip,
                    'contact_name'  => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark'       => '',
                'total_amount' => $sku->price * $amount,
                'type'         => Order::TYPE_CROWDFUNDING,
            ]);

            //订单关联用户
            $order->user()->associate($user);
            //订单写入数据库
            $order->save();
            //因为众筹商品的订单是只用一个sku的所以不用遍历
            //直接创建一个新的订单项与SKU关联
            $item = $order->items()->make([
                'amount' => $amount,
                'price' => $sku->price,
            ]);
            //订单项关联商品
            $item->product()->associate($sku->product_id);
            //订单项关联商品sku
            $item->productSku()->associate($sku);
            //订单项写入数据库
            $item->save();

            // 扣减对应 SKU 库存
            if ($sku->decreaseStock($amount) <= 0) {
                throw new InvalidRequestException('该商品库存不足');
            }

            return $order;
        });

        //得到现在到众筹结束的时间
        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();
        // 剩余秒数与默认订单关闭时间取较小值作为订单关闭时间
        dispatch(new CloseOrder($order, min(config('app.order_ttl'), $crowdfundingTtl)));

        return $order;
    }

    //秒杀商品下单逻辑
    // 将原本的 UserAddress 类型改成 array 类型
    public function seckill(User $user, array $addressData, ProductSku $sku)
    {
        //开启事务
        // 将 $addressData 传入匿名函数
        $order = \DB::transaction(function () use ($user, $addressData, $sku) {
            
              //创建一个订单
              $order = new Order([
                  'address'      => [ // address 字段直接从 $addressData 数组中读取
                      'address'       => $addressData['province'].$addressData['city'].$addressData['district'].$addressData['address'],
                      'zip'           => $addressData['zip'],
                      'contact_name'  => $addressData['contact_name'],
                      'contact_phone' => $addressData['contact_phone'],
                  ],
                  'remark'     => '',
                  'total_amount' => $sku->price,
                  'type'        =>  Order::TYPE_SECKILL,
              ]);
              //订单关联到当前用户
              $order->user()->associate($user);
              //写入数据库
              $order->save();
              //创建订单项
              $item = $order->items()->make([
                  'amount' => 1, //秒杀商品只能买一份
                  'price'  => $sku->price,
              ]);
              //订单项关联商品
              $item->product()->associate($sku->product_id);
              //订单项关联商品sku
              $item->productSku()->associate($sku);
              //写入数据库
              $item->save();
              //扣减对应SKU库存
              // 扣减对应 SKU 库存
              if ($sku->decreaseStock(1) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
              }

              return $order;
        });
        //开启秒杀订单的到时关闭任务
        dispatch(new CloseOrder($order,config('app.seckill_order_ttl')));

        return $order;
    }

    //退款逻辑
    public function refundOrder(Order $order)
    {
        // 判断该订单的支付方式
        switch ($order->payment_method) {
            case 'wechat':
                // 生成退款订单号
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no,
                    'total_fee' => $order->total_amount * 100,
                    'refund_fee' => $order->total_amount * 100,
                    'out_refund_no' => $refundNo,
                    'notify_url' => ngrok_url('payment.wechat.refund_notify'),
                ]);
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                $refundNo = Order::getAvailableRefundNo();
                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no,
                    'refund_amount' => $order->total_amount,
                    'out_request_no' => $refundNo,
                ]);
                if ($ret->sub_code) {
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra,
                    ]);
                } else {
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            case 'installment':
                $order->update([
                    'refund_no' => Order::getAvailableRefundNo(), // 生成退款订单号
                    'refund_status' => Order::REFUND_STATUS_PROCESSING, // 将退款状态改为退款中
                ]);
                // 触发退款异步任务
                dispatch(new RefundInstallmentOrder($order));
                break;
            default:
                throw new InternalException('未知订单支付方式：'.$order->payment_method);
                break;
        }
    }
}
