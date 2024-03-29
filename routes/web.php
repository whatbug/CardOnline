<?php
Route::get('/', 'HomeController@shop');
if (config('app.debug')) {
    Route::get('/install', 'DevController@install');
    Route::get('/test', 'DevController@test'); }
    Route::get('/storage/{file_path}', 'Merchant\\File@renderImage')->where('file_path', '.+');
    Route::get('/pay/{order_no}', 'Shop\\Pay@pay');
    Route::any('/pay/return/{pay_id}/{out_trade_no}', 'Shop\\Pay@payReturn');
    Route::any('/pay/return/{pay_id}', 'Shop\\Pay@payReturn');
    Route::any('/pay/notify/{pay_id}', 'Shop\\Pay@payNotify');
    Route::get('/qrcode/pay/{order_no}/{pay_file}', 'Shop\\Pay@qrcode');
    Route::get('/pay/result/{order_no}', 'Shop\\Pay@result')->name('pay.result');
    Route::get('/admin', 'HomeController@admin');
    Route::get('/admin/{all}', 'HomeController@admin')->where('all', '.*');
    Route::get('/c/{en_category_id}', 'HomeController@shop_category');
    Route::get('/p/{en_product_id}', 'HomeController@shop_product');
    Route::get('/s', 'HomeController@shop_default');