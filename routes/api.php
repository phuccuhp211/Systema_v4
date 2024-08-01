<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Controller_Admin as Admin;
use App\Http\Controllers\Client\Controller_Pay as Pay;
use App\Http\Controllers\Client\Controller_Cart as Cart;
use App\Http\Controllers\Client\Controller_Client as Client;
use App\Http\Controllers\Controller_Account as Account;

use App\Http\Middleware\Midd_Cart;
use App\Http\Middleware\Midd_AdminLog;
use App\Http\Middleware\Midd_Payment_Protect;

Route::get('/', [Client::class, 'index'])->name('home');
Route::match(['get','post'] ,'/products/{type}/{data?}', [Client::class, 'products'])->name('show');
Route::get('/cart', [Client::class, 'cart'])->name('cart');
Route::get('/detail/{data}', [Client::class, 'detail'])->name('detail');
Route::get('/config', [Client::class, 'config'])->name('config');
Route::get('/pay', [Client::class, 'pay'])->name('pay');
Route::get('/complete_order', [Client::class, 'dord'])->name('dord');
Route::get('/reset_password', [Client::class, 'rspw'])->name('rspw');
Route::match(['get','post'] ,'/invoice_check', [Client::class, 'inv_check'])->name('invoice');
Route::post('/comment', [Client::class, 'comment'])->name('cmt');
Route::post('/rating', [Client::class, 'rate'])->name('rate');

Route::middleware([payment::class])->group(function () {
    Route::post('/payment/checkip', [Pay::class, 'validation'])->name('payment.vli');
    Route::post('/payment/addcp', [Pay::class, 'applycoupon'])->name('payment.dcp');
    Route::post('/payment/order', [Pay::class, 'order'])->name('payment.ord');
    Route::post('/payment/store', [Pay::class, 'store'])->name('payment.str');
});

Route::middleware([cart::class])->group(function () {
    Route::get('/cart/buy/{id}', [Cart::class, 'buy'])->name('cart.buy');
    Route::post('/cart/add', [Cart::class, 'add'])->name('cart.add');
    Route::post('/cart/fix', [Cart::class, 'fix'])->name('cart.fix');
    Route::post('/cart/del', [Cart::class, 'del'])->name('cart.del');
    Route::post('/cart/dac', [Cart::class, 'dac'])->name('cart.dac');
});

Route::match(['get', 'post'], '/user/client/{type}', [Account::class, 'client_lls'])->name('client');
Route::match(['get', 'post'], '/user/admin/{type}', [Account::class, 'admin_lls'])->name('admin');

Route::get('/admin', [Admin::class, 'login'])->name('alog');
Route::middleware([adminlog::class])->group(function () {
    Route::match(['get','post'] ,'manager/{type?}', [Admin::class, 'manager'])->name('manager');
    Route::match(['get','post'] ,'manager/ss/{type}/{id?}', [Admin::class, 'ss_mng'])->name('manager.ss');
    Route::match(['get','post'] ,'manager/bn/{type}/{id?}', [Admin::class, 'bn_mng'])->name('manager.bn');
    Route::match(['get','post'] ,'manager/pd/{type}/{id?}', [Admin::class, 'pd_mng'])->name('manager.pd');
    Route::match(['get','post'] ,'manager/c1/{type}/{id?}', [Admin::class, 'c1_mng'])->name('manager.c1');
    Route::match(['get','post'] ,'manager/c2/{type}/{id?}', [Admin::class, 'c2_mng'])->name('manager.c2');
    Route::match(['get','post'] ,'manager/us/{type}/{id?}', [Admin::class, 'us_mng'])->name('manager.us');
    Route::match(['get','post'] ,'manager/cm/{type}/{id?}', [Admin::class, 'cm_mng'])->name('manager.cm');
    Route::match(['get','post'] ,'manager/in/{type}/{id?}', [Admin::class, 'in_mng'])->name('manager.in');
    Route::match(['get','post'] ,'manager/cp/{type}/{id?}', [Admin::class, 'cp_mng'])->name('manager.cp');
    Route::post('manager/check/permission', [Admin::class, 'check_permission'])->name('manager.checkpm');
    Route::post('manager/filter/ajax', [Admin::class, 'filter'])->name('manager.filter');
});

Route::post('/vnpay_payment', [Pay::class, 'vnpay_payment'])->name('vnpay.payment');
Route::get('/vnpay_result', [Pay::class, 'vnpay_result'])->name('vnpay.result');
