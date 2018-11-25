<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductProperty extends Model
{
    //可以直接写入和修改的字段
    protected $fillable = [
        'name',
        'value',
    ];

    //没有created_at 和 updated_at 字段
    public $timestamps = false;

    //模型关联 由商品属性得到对应的商品
    public function product(){

        return $this->belongsTo(Product::class);
    }

}
