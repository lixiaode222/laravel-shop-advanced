<?php

//秒杀商品下单逻辑
Route::post('seckill_orders','OrdersController@seckill')->name('seckill_orders.store');

//首页
Route::redirect('/', '/products')->name('root');
////商品列表页面
Route::get('products', 'ProductsController@index')->name('products.index');
//登陆注册的相关路由
Auth::routes();

//路由组 只有登陆后的用户才能访问
Route::group(['middleware' => 'auth'], function() {
    //邮箱验证提醒页面
    Route::get('/email_verify_notice', 'PagesController@emailVerifyNotice')->name('email_verify_notice');
    //邮箱验证逻辑
    Route::get('/email_verification/verify', 'EmailVerificationController@verify')->name('email_verification.verify');
    //发送邮件
    Route::get('/email_verification/send', 'EmailVerificationController@send')->name('email_verification.send');

    //路由组 只有通过邮箱验证后的用户才能访问
    Route::group(['middleware' => 'email_verified'], function() {
        //用户收货地址列表页面
        Route::get('user_addresses', 'UserAddressesController@index')->name('user_addresses.index');
        //用户添加收货地址页面
        Route::get('user_addresses/create', 'UserAddressesController@create')->name('user_addresses.create');
        //用户添加收货地址逻辑
        Route::post('user_addresses', 'UserAddressesController@store')->name('user_addresses.store');
        //用户修改收货地址页面
        Route::get('user_addresses/{user_address}', 'UserAddressesController@edit')->name('user_addresses.edit');
        //用户修改收货地址逻辑
        Route::put('user_addresses/{user_address}', 'UserAddressesController@update')->name('user_addresses.update');
        //用户删除收货地址逻辑
        Route::delete('user_addresses/{user_address}', 'UserAddressesController@destroy')->name('user_addresses.destroy');
        //用户收藏商品逻辑
        Route::post('products/{product}/favorite', 'ProductsController@favor')->name('products.favor');
        //用户取消收藏商品逻辑
        Route::delete('products/{product}/favorite', 'ProductsController@disfavor')->name('products.disfavor');
        //用户收藏商品列表页面
        Route::get('products/favorites', 'ProductsController@favorites')->name('products.favorites');
        //用户将商品加入购物车逻辑
        Route::post('cart', 'CartController@add')->name('cart.add');
        //用户购物车列表页面
        Route::get('cart', 'CartController@index')->name('cart.index');
        //用户移除购物车项逻辑
        Route::delete('cart/{sku}', 'CartController@remove')->name('cart.remove');
        //用户下单逻辑
        Route::post('orders', 'OrdersController@store')->name('orders.store');
        //用户的订单页面
        Route::get('orders', 'OrdersController@index')->name('orders.index');
        //订单详情
        Route::get('orders/{order}', 'OrdersController@show')->name('orders.show');
        //用户收货逻辑
        Route::post('orders/{order}/received', 'OrdersController@received')->name('orders.received');
        //用户使用支付宝支付订单逻辑
        Route::get('payment/{order}/alipay', 'PaymentController@payByAlipay')->name('payment.alipay');
        //支付宝前端回调
        Route::get('payment/alipay/return', 'PaymentController@alipayReturn')->name('payment.alipay.return');
        //用户评价页面
        Route::get('orders/{order}/review', 'OrdersController@review')->name('orders.review.show');
        //用户评价逻辑
        Route::post('orders/{order}/review', 'OrdersController@sendReview')->name('orders.review.store');
        //用户申请退款逻辑
        Route::post('orders/{order}/apply_refund', 'OrdersController@applyRefund')->name('orders.apply_refund');
        //用户的优惠券信息
        Route::get('coupon_codes/{code}', 'CouponCodesController@show')->name('coupon_codes.show');
        //众筹商品的下单逻辑
        Route::post('crowdfunding_orders', 'OrdersController@crowdfunding')->name('crowdfunding_orders.store');
        //用户分期付款逻辑
        Route::post('payment/{order}/installment', 'PaymentController@payByInstallment')->name('payment.installment');
        //用户分期列表页面
        Route::get('installments','InstallmentsController@index')->name('installments.index');
        //用户分期详情页面
        Route::get('installments/{installment}','InstallmentsController@show')->name('installments.show');
        //用户分期付款支付宝页面拉起
        Route::get('installments/{installment}/alipay', 'InstallmentsController@payByAlipay')->name('installments.alipay');
        //分期付款支付宝前端回调
        Route::get('installments/alipay/return', 'InstallmentsController@alipayReturn')->name('installments.alipay.return');
    });
});

//商品详情页面
Route::get('products/{product}', 'ProductsController@show')->name('products.show');
//支付宝后台回调
Route::post('payment/alipay/notify', 'PaymentController@alipayNotify')->name('payment.alipay.notify');
//分期付款支付宝后端回调   不能放在 auth 中间件中
Route::post('installments/alipay/notify', 'InstallmentsController@alipayNotify')->name('installments.alipay.notify');

