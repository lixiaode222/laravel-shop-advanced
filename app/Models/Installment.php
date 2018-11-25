<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    //定义分期状态
    const STATUS_PENDING = 'pending';
    const STATUS_REPAYING = 'repaying';
    const STATUS_FINISHED = 'finished';

    //把分期状态和中文描述对应起来
    public static $statusMap = [
        self::STATUS_PENDING => '未执行',
        self::STATUS_REPAYING => '还款中',
        self::STATUS_FINISHED => '已完成',
    ];


    //可以直接写入和修改的字段
    protected $fillable = [
        'no',
        'total_amount',
        'count',
        'fee_rate',
        'fine_rate',
        'status'
    ];

    //模型关联 由分期得到对应的用户
    public function user(){

        return $this->belongsTo(User::class);
    }

    //模型关联 由分期得到对应的订单
    public function order(){

        return $this->belongsTo(Order::class);
    }

    //模型关联 由分期得到对应的分期项
    public function items(){

        return $this->hasMany(InstallmentItem::class);
    }

    //在分期创建时自动生成一个唯一的分期流水号
    protected static function boot()
    {
        parent::boot();
        // 监听模型创建事件，在写入数据库之前触发
        static::creating(function ($model) {
            // 如果模型的 no 字段为空
            if (!$model->no) {
                // 调用 findAvailableNo 生成订单流水号
                $model->no = static::findAvailableNo();
                // 如果生成失败，则终止创建订单
                if (!$model->no) {
                    return false;
                }
            }
        });
    }

    //生成分期流水号
    public static function findAvailableNo()
    {
        // 分期流水号前缀
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++) {
            // 随机生成 6 位的数字
            $no = $prefix.str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            // 判断是否已经存在
            if (!static::query()->where('no', $no)->exists()) {
                return $no;
            }
        }
        \Log::warning(sprintf('find installment no failed'));

        return false;
    }

}
