<?php

namespace App\Services;

use App\Models\Category;

class CategoryService {

    //这是一个递归方法 得到类目树
    //$parentId 参数代表要获取子类目的父类目的ID，null代表获取所有根类目
    //$allcategories 参数代表数据库中的所有类目，null代表需要从数据库中查询
    public function getCategoryTree($parentId = null ,$allcategories = null){

        //如果$allcategories 为null就需要去数据库中查询
        if (is_null($allcategories)){
            $allcategories = Category::all();
        }

        return $allcategories
               //从所有类目中筛选出父类目是$parentId的类目
               ->where('parent_id',$parentId)
                //遍历这些类目，并且返回值构建一个新的集合
               ->map(function (Category $category) use ($allcategories){
                    $data = ['id' => $category->id,'name' => $category->name];
                    //如果当前类目不是父类目，则直接返回
                    if(!$category->is_directory){
                        return $data;
                    }
                    //否则递归调用本方法，将返回值放入children字段中
                    $data['children'] = $this->getCategoryTree($category->id,$allcategories);

                    return $data;
               });
    }
}