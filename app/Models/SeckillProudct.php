<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SeckillProudct extends Model
{
    //可直接写入和修改的字段
    protected $fillable = [
        'start_at',
        'end_at',
    ];

    //表明 start_at 和 end_at 这两个字段是日期时间类型
    protected $dates = [
        'start_at',
        'end_at',
    ];

    //表明没有时间戳
    public $timestamps = false;

    //模型关联 由秒杀得到对应的商品
    public function product(){

        return $this->belongsTo(Product::class);
    }

    //定义一个名为 is_before_start 的访问器，如果当前时间早于开始时间就返回 true
    public function getIsBeforeStartAttribute(){

        return Carbon::now()->lt($this->start_at);
    }

    //定义一个名为 is_after_end 的访问器，当前时间晚于秒杀结束时间时返回true
    public function getIsAfterEndAttribute(){

        return Carbon::now()->gt($this->end_at);
    }

}
