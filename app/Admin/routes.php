<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    //后台首页
    $router->get('/', 'HomeController@index');
    //后台用户列表页面
    $router->get('users', 'UsersController@index');
    //后台商品列表页面
    $router->get('products', 'ProductsController@index');
    //后台创建商品列表页面
    $router->get('products/create', 'ProductsController@create');
    //后台创建商品逻辑
    $router->post('products', 'ProductsController@store');
    //后台编辑商品页面
    $router->get('products/{id}/edit', 'ProductsController@edit');
    //后台编辑商品逻辑
    $router->put('products/{id}', 'ProductsController@update');
    //后台订单列表页面
    $router->get('orders', 'OrdersController@index')->name('admin.orders.index');
    //后台订单详情页面
    $router->get('orders/{order}', 'OrdersController@show')->name('admin.orders.show');
    //后台发货逻辑
    $router->post('orders/{order}/ship', 'OrdersController@ship')->name('admin.orders.ship');
    //后台退款逻辑
    $router->post('orders/{order}/refund', 'OrdersController@handleRefund')->name('admin.orders.handle_refund');
    //后台优惠券列表页面
    $router->get('coupon_codes', 'CouponCodesController@index');
    //后台创建优惠券页面
    $router->get('coupon_codes/create', 'CouponCodesController@create');
    //后台创建优惠券逻辑
    $router->post('coupon_codes', 'CouponCodesController@store');
    //后台编辑优惠价页面
    $router->get('coupon_codes/{id}/edit', 'CouponCodesController@edit');
    //后台编辑优惠券逻辑
    $router->put('coupon_codes/{id}', 'CouponCodesController@update');
    //后台删除优惠券逻辑
    $router->delete('coupon_codes/{id}', 'CouponCodesController@destroy');
    //后台商品分类列表
    $router->get('categories','CategoriesController@index');
    //后台创建商品分类页面
    $router->get('categories/create','CategoriesController@create');
    //后台创建商品分类逻辑
    $router->post('categories','CategoriesController@store');
    //后台编辑商品分类页面
    $router->get('categories/{id}/edit','CategoriesController@edit');
    //后台编辑商品分类逻辑
    $router->put('categories/{id}','CategoriesController@update');
    //后台删除商品分类逻辑
    $router->delete('categories/{id}','CategoriesController@destroy');
    //后台下拉搜索框接口
    $router->get('api/categories', 'CategoriesController@apiIndex');
    //后台众筹商品列表页面
    $router->get('crowdfunding_products','CrowdfundingProductsController@index');
    //后台创建众筹商品页面
    $router->get('crowdfunding_products/create','CrowdfundingProductsController@create');
    //后台创建众筹商品逻辑
    $router->post('crowdfunding_products','CrowdfundingProductsController@store');
    //后台编辑众筹商品页面
    $router->get('crowdfunding_products/{id}/edit','CrowdfundingProductsController@edit');
    //后台编辑众筹商品逻辑
    $router->put('crowdfunding_products/{id}','CrowdfundingProductsController@update');

});