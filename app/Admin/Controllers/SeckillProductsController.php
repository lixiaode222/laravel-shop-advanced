<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use Encore\Admin\Form;
use Encore\Admin\Grid;


class SeckillProductsController extends CommonProductsController
{

     //实现抽象类方法得到商品的类型
    public function getProductType(){

        return Product::TYPE_SECKILL;
    }

    //实现抽象类德方法后台秒杀商品的列表的表格形式
    protected function customGrid(Grid $grid){

        $grid->id('ID')->sortable();
        $grid->title('商品名称');
        $grid->on_sale('已上架')->display(function ($value){
              return $value ? '是' : '否';
        });
        $grid->price('价格');
        $grid->column('seckill.start_at','开始时间');
        $grid->column('seckill.start_at','结束时间');
        $grid->sold_count('销量');
    }

    //实现抽象类方法后台秒杀商品的添加和修改表格
    protected function customForm(Form $form){

        //秒杀相关字段
        $form->datetime('seckill.start_at','秒杀开始时间')->rules('required|date');
        $form->datetime('seckill.end_at','秒杀结束时间')->rules('required|date');
    }
}
