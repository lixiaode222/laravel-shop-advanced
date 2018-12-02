<?php

namespace App\Http\Requests;


use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Validation\Rule;

class SeckillOrderRequest extends Request
{


    public function rules()
    {
        return [
            'address_id' => [
                'required',
                Rule::exists('user_addresses','id')->where('user_id',$this->user()->id)
            ],
            'sku_id' => [
                'required',
                function($attribute,$value,$fail){
                       if(!$sku = ProductSku::find($value)){
                            return $fail('该商品不存在');
                       }
                       if($sku->product->type !== Product::TYPE_SECKILL){
                            return $fail('该商品不支持秒杀');
                       }
                       if($sku->product->seckill->is_before_start){
                           return $fail('秒杀尚未开始');
                       }
                       if($sku->product->seckill->is_after_end){
                            return $fail('秒杀已经结束');
                       }
                       if(!$sku->product->on_sale){
                            return $fail('该商品未上架');
                       }
                       if($sku->stock < 1){
                            return $fail('该商品已售完');
                       }

                       //秒杀只能下单一次 所以在下单前要判断是否已经买过了
                       if($order = Order::query()
                                 //筛选出当前用户的订单
                                 ->where('user_id',$this->user()->id)
                                 //包含了当期SKU的订单
                                 ->whereHas('items',function($query) use ($value){
                                       $query->where('product_sku_id',$value);
                                 })
                                 //已经支付过的订单
                                 ->where(function ($query){
                                       $query->whereNotNull('paid_at')
                                             //或者未关闭的订单
                                             ->orWhere('closed',false);
                                 })
                                 ->first()){
                                 if($order->paid_at){
                                     return $fail('你已经抢购过该商品了');
                                 }
                                 return $fail('你已经下单了该商品，请到订单页面支付');
                       }

                }
            ],
        ];
    }
}
