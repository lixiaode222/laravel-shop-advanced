<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Models\CrowdfundingProduct;

class CrowdfundingProductsController extends CommonProductsController
{

    //实现基础的抽象类中的得到当前商品类型的方法
    public function getProductType()
    {
        return Product::TYPE_CROWDFUNDING;
    }

    //实现继承的抽象类中当前商品类别展示的列表形式
    protected function customGrid(Grid $grid){

        $grid->id('ID')->sortable();
        $grid->title('商品名称');
        $grid->on_sale('已上架')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->price('价格');
        $grid->column('crowdfunding.target_amount', '目标金额');
        $grid->column('crowdfunding.end_at', '结束时间');
        $grid->column('crowdfunding.total_amount', '目前金额');
        $grid->column('crowdfunding.status', ' 状态')->display(function ($value) {
            return CrowdfundingProduct::$statusMap[$value];
        });
    }

    //实现继承的抽象类中当前商品类别创建表单的特殊字段
    protected function customForm(Form $form)
    {
        // 众筹相关字段
        $form->text('crowdfunding.target_amount', '众筹目标金额')->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at', '众筹结束时间')->rules('required|date');
    }

}
