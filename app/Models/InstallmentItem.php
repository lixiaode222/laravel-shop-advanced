<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Moontoast\Math\BigNumber;

class InstallmentItem extends Model
{
    //定义退款状态
    const REFUND_STATUS_PENDING = 'pending';
    const REFUND_STATUS_PROCESSING = 'processing';
    const REFUND_STATUS_SUCCESS = 'success';
    const REFUND_STATUS_FAILED = 'failed';

    //把退款状态和中文描述对应起来
    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING    => '未退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_SUCCESS    => '退款成功',
        self::REFUND_STATUS_FAILED     => '退款失败',
    ];

    //可直接写入和修改的字段
    protected $fillable = [
        'sequence',
        'base',
        'fee',
        'fine',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_no',
        'refund_status',
    ];

    //表明这两个是日期时间类型
    protected $dates = ['due_date', 'paid_at'];

    //模型关联 由分期项得到对应的分期
    public function installment(){

        return $this->belongsTo(Installment::class);
    }

    //创建一个访问器，返回当前还款计划需要还的总金额
    public function getTotalAttribute()
    {
        $total = (new BigNumber($this->base, 2))->add($this->fee);
        if (!is_null($this->fine)) {
            $total->add($this->fine);
        }

        return $total->getValue();
    }

    //创建一个访问器，返回当前还款计划是否已经逾期
    public function getIsOverdueAttribute()
    {
        return Carbon::now()->gt($this->due_date);
    }
}
