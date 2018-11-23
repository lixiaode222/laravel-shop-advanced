<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrowdfundingProduct extends Model
{
    //定义众筹的三种状态
    const STATUS_FUNDING = 'funding';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    //把这三种状态和中文描述对应起来
    public static $statusMap = [
        self::STATUS_FUNDING => '众筹中',
        self::STATUS_SUCCESS => '众筹成功',
        self::STATUS_FAIL => '众筹失败',
    ];

    //可直接写入和修改的字段
    protected $fillable = [
        'total_amount',
        'target_amount',
        'user_count',
        'end_at',
        'status',
    ];

    //表明`end_at`是日期时间类型
    protected $dates = ['end_at'];

    //表明这个模型没有created_at 和 updated_at
    public $timestamps = false;

    //模型关联 由众筹商品得到对应的商品
    public function product(){

        return $this->belongsTo(Product::class);
    }

    //定义一个名为 percent 的访问器,得到当前众筹的进度
    public function getPercentAttribute(){

        //进度 = 已经筹到的金额 / 目标金额
        $value = $this->attributes['total_amount'] / $this->attributes['target_amount'];

        return floatval(number_format($value*100,2,'.',''));
    }
}
