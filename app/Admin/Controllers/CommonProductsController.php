<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use App\Models\Category;

abstract class CommonProductsController extends Controller{

    use HasResourceActions;

    //定义一个抽象方法，各个类型的控制器来实现这个方法返回当前管理的商品类型
    abstract public function getProductType();

    //后台商品列表页面
    public function index(Content $content){

        return $content
                ->header(Product::$typeMap[$this->getProductType()].'列表')
                ->body($this->grid());
    }

    //后台编辑商品页面
    public function edit($id,Content $content){

        return $content
                 ->header('编辑'.Product::$typeMap[$this->getProductType()])
                 ->body($this->form()->edit($id));
    }

    //后台创建商品页面
    public function create(Content $content){

        return $content
                 ->header('创建'.Product::$typeMap[$this->getProductType()])
                 ->body($this->form());
    }

    //定义一个抽象方法，各个类型的控制器来实现这个方法定义列表的字段和属性
    abstract protected function customGrid(Grid $grid);

    //后台商品类别的展示表格形式
    protected function grid(){

        $grid = new Grid(new Product());

        //筛选出当前类型的商品，默认按ID倒序排序
        $grid->model()->where('type',$this->getProductType())->orderBy('id','desc');

        //调用自定义的方法来显示表格
        $this->customGrid($grid);

        //禁用查看和删除按钮
        $grid->actions(function ($actions){
            $actions->disableView();
            $actions->disableDelete();
        });

        //禁用全部删除按钮
        $grid->tools(function ($tools){
            $tools->batch(function ($batch){
                $batch->disableDelete();
            }) ;
        });

        return $grid;
    }

    //定义一个抽象方法，各个类型的控制器来实现这个方法定义表单的字段和属性
    abstract protected function customForm(Form $form);

    //后台商品的创建表单形式
    protected function form(){

        $form = new Form(new Product());

        //在表单页面添加一个名为type的隐藏字段，值为当前商品类型
        $form->hidden('type')->value($this->getProductType());
        $form->text('title','商品名称')->rules('required');
        $form->select('category_id', '分类')->options(function ($id) {
            $category = Category::find($id);
            if ($category) {
                return [$category->id => $category->full_name];
            }
        })->ajax('/admin/api/categories?is_directory=0');
        $form->image('image', '图片')->rules('required|image');
        $form->editor('description','商品描述')->rules('required');
        $form->radio('on_sale', '上架')->options(['1'=>'是','0'=>'否'])->default('0');

        //调用自定义的方法来显示各类型商品不一样的字段
        $this->customForm($form);

        //关联商品SKU
        $form->hasMany('skus', '商品 SKU', function (Form\NestedForm $form) {
            $form->text('title', 'SKU 名称')->rules('required');
            $form->text('description', 'SKU 描述')->rules('required');
            $form->text('price', '单价')->rules('required|numeric|min:0.01');
            $form->text('stock', '剩余库存')->rules('required|integer|min:0');
        });

        //通过所有的商品SKU得到最低的价格赋值给商品价格
        $form->saving(function (Form $form) {
            $form->model()->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
        });

        return $form;
    }
}