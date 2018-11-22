<?php

namespace App\Models;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    //可直接写入和修改的字段
    protected $fillable = [
        'title',
        'description',
        'image',
        'on_sale',
        'rating',
        'sold_count',
        'review_count',
        'price'
    ];


    protected $casts = [
        'on_sale' => 'boolean', // on_sale 是一个布尔类型的字段
    ];


    //模型关联 由商品得到对应的商品SKU
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    //模型关联 由商品得到它的分类
    public function category(){

        return $this->belongsTo(Category::class);
    }


    //构造一个访问器,得到商品图片的完整路径
    public function getImageUrlAttribute()
    {
        // 如果 image 字段本身就已经是完整的 url 就直接返回
        if (Str::startsWith($this->attributes['image'], ['http://', 'https://'])) {
            return $this->attributes['image'];
        }
        return \Storage::disk('public')->url($this->attributes['image']);
    }
}
