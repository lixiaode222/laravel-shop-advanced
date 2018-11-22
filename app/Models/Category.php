<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //可直接写入和修改的字段
    protected $fillable = [
        'name',
        'is_directory',
        'level',
        'path'
    ];

    //表明`is_directory`字段是布尔型
    protected $casts = ['is_directory' => 'boolean'];

    //在生成目录的时候补上`path`字段
    protected static function boot()
    {
        parent::boot();
        //监听Category的创建事件,用于初始化path和level字段值
        static::creating(function (Category $category){
              //如果创建的是一个根目录
              if(is_null($category->parent_id)){
                  //将 level 设为 0
                  $category->level = 0;
                  //将 path 设为 -
                  $category->path = '-';
              }else{
                  //如果不是根目录
                  //将层级设为父类目录的 level+1
                  $category->level = $category->parent->level + 1;
                  //将 path 值设为父类目录的 path 追加父类目录ID以及一个'-'符号
                  $category->path = $category->parent->path.$category->parent_id.'-';
              }
        });
    }

    //模型关联 得到目录的父类目录
    public function parent(){

        return $this->belongsTo(Category::class);
    }

    //模型关联 得到目录的所有子目录
    public function children(){

        return $this->hasMany(Category::class,'parent_id');
    }

    //模型关联 得到目录下的所有商品
    public function products(){

        return $this->hasMany(Product::class);
    }

    //定义一个访问器，获取所有祖先类目的ID的值
    public function getPathIdsAttribute(){

        //通过`path`字段来得到祖先类目的ID
        //通过上面的方法可知 `path`的值 大概为  -1-2-3-4-
        //trim()方法来去掉两边的-
        //explode()将字符串以 - 分割成数组
        //array_filter() 来将数组的空值移除
        return array_filter(explode('-',trim($this->path,'-')));
    }

    //定义一个访问器，获取所有祖先类目并且按层级排序
    public function getAncestorsAttribute(){

        //通过上面的访问器得到数组，然后进行排序
        return Category::query()
                       ->whereIn('id',$this->path_ids)
                       ->orderBy('level')
                       ->get();
    }

    //定义一个访问器，获取所有祖先类目名称以及当前类目的名称
    public function getFullNameAttribute(){

         return $this->ancestors      //获取了按层级排序的祖先类目的查询内容
                     ->pluck('name')  //取出祖先类目中的name字段作为一个数组
                     ->push($this->name) //将当前类目的名称加到数组的最后面
                     ->implode(' - ');    //通过数组得到完整的字符串
    }
}
