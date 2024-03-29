<?php
namespace App\Http\Controllers\Shop;
use App\Card;
use App\Category;
use App\Library\FundHelper;
use App\Library\Helper;
use App\Library\LogHelper;
use App\Product;
use App\Library\Response;
use \Gateway\Pay\Pay as GatewayPay;
use App\Library\Geetest;
use App\Mail\ProductCountWarn;
use App\System;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class Pay extends Controller {

    public function __construct() {
        define('SYS_NAME', config('app.name'));
        define('SYS_URL', config('app.url'));
        define('SYS_URL_API', config('app.url_api'));
    }

    private $payApi = null;
    public function goPay($sp13451b, $sp6b06ff, $sp656002, $sp2e6adb, $sp50cd10) {
        try {
            $sp0451dd = json_decode($sp2e6adb->config, true);
            $sp0451dd['payway'] = $sp2e6adb->way;
            GatewayPay::getDriver($sp2e6adb)->goPay($sp0451dd, $sp6b06ff, $sp656002, $sp656002, $sp50cd10);
            return self::renderResultPage($sp13451b, array('success' => false, 'title' => trans('shop.please_wait'), 'msg' => trans('shop.please_wait_for_pay')));
        } catch (\Exception $sp45222f) {
            return self::renderResultPage($sp13451b, array('msg' => $sp45222f->getMessage()));
        }
    }

    function buy(Request $sp13451b) {
        $sp9a876f = $sp13451b->input('customer');
        if (strlen($sp9a876f) !== 32) {
            return self::renderResultPage($sp13451b, array('msg' => '提交超时，请刷新购买页面并重新提交<br><br>
当前网址: ' . $sp13451b->getQueryString() . '
提交内容: ' . var_export($sp9a876f) . ', 提交长度:' . strlen($sp9a876f) . '<br>
若您刷新后仍然出现此问题. 请加网站客服反馈'));
        }
        if (System::_getInt('vcode_shop_buy') === 1) {
            try { $this->validateCaptcha($sp13451b);
            } catch (\Throwable $sp45222f) {
                return self::renderResultPage($sp13451b, array('msg' => trans('validation.captcha')));
            }
        }
        $sp3955fa = (int) $sp13451b->input('category_id');
        $spcaeba2 = (int) $sp13451b->input('product_id');
        $sp1ca412 = (int) $sp13451b->input('count');
        $spdabd4e = $sp13451b->input('coupon');
        $sp6c47af = $sp13451b->input('contact');
        $sp05a58e = $sp13451b->input('contact_ext') ?? null;
        $spd55aa5 = !empty(@json_decode($sp05a58e, true)['_mobile']);
        $sp56237f = (int) $sp13451b->input('pay_id');
        if (!$sp3955fa || !$spcaeba2) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.required')));
        }
        if (strlen($sp6c47af) < 1) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.contact.required')));
        }
        $spd58c4f = Category::findOrFail($sp3955fa);
        $sp863814 = Product::where('id', $spcaeba2)->where('category_id', $sp3955fa)->where('enabled', 1)->with(array('user'))->first();
        if ($sp863814 == null || $sp863814->user == null) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.not_found')));
        }
        if (!$sp863814->enabled) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.not_on_sell')));
        }
        if ($sp863814->password_open) {
            if ($sp863814->password !== $sp13451b->input('product_password')) {
                return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.password_error')));
            }
        } else {
            if ($spd58c4f->password_open) {
                if ($spd58c4f->password !== $sp13451b->input('category_password')) {
                    if ($spd58c4f->getTmpPassword() !== $sp13451b->input('category_password')) {
                        return self::renderResultPage($sp13451b, array('msg' => trans('shop.category.password_error')));
                    }
                }
            }
        }
        if ($sp1ca412 < $sp863814->buy_min) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.buy_min', array('num' => $sp863814->buy_min))));
        }
        if ($sp1ca412 > $sp863814->buy_max) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.buy_max', array('num' => $sp863814->buy_max))));
        }
        if ($sp863814->count < $sp1ca412) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.out_of_stock')));
        }
        $sp2e6adb = \App\Pay::find($sp56237f);
        if ($sp2e6adb == null || !$sp2e6adb->enabled) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.pay.not_found')));
        }
        $sp261316 = $sp863814->price;
        if ($sp863814->price_whole) {
            $sp5248c4 = json_decode($sp863814->price_whole, true);
            for ($sp51a993 = count($sp5248c4) - 1; $sp51a993 >= 0; $sp51a993--) {
                if ($sp1ca412 >= (int) $sp5248c4[$sp51a993][0]) {
                    $sp261316 = (int) $sp5248c4[$sp51a993][1]; break;
                }
            }
        }
        $sp6fa047 = $sp1ca412 * $sp261316;
        $sp50cd10 = $sp6fa047;
        $sp5698b2 = 0;
        $sp3c9138 = null;
        if ($sp863814->support_coupon && strlen($spdabd4e) > 0) {
            $spd4c074 = \App\Coupon::where('user_id', $sp863814->user_id)->where('coupon', $spdabd4e)->where('expire_at', '>', Carbon::now())->whereRaw('`count_used`<`count_all`')->get();
            foreach ($spd4c074 as $sp55c7ac) {
                if ($sp55c7ac->category_id === -1 || $sp55c7ac->category_id === $sp3955fa && ($sp55c7ac->product_id === -1 || $sp55c7ac->product_id === $spcaeba2)) {
                    if ($sp55c7ac->discount_type === \App\Coupon::DISCOUNT_TYPE_AMOUNT && $sp50cd10 >= $sp55c7ac->discount_val) {
                        $sp3c9138 = $sp55c7ac; $sp5698b2 = $sp55c7ac->discount_val; break;
                    }
                    if ($sp55c7ac->discount_type === \App\Coupon::DISCOUNT_TYPE_PERCENT) {
                        $sp3c9138 = $sp55c7ac; $sp5698b2 = (int) round($sp50cd10 * $sp55c7ac->discount_val / 100); break;
                    }
                }
            }
            if ($sp3c9138 === null) {
                return self::renderResultPage($sp13451b, array('msg' => trans('shop.coupon.invalid')));
            }
            $sp50cd10 -= $sp5698b2;
        } $spd81ea0 = (int) round($sp50cd10 * $sp2e6adb->fee_system);
        $sp1ae43e = $sp50cd10 - $spd81ea0; $sp188cde = $spd55aa5 ? System::_getInt('sms_price', 10) : 0;
        $sp50cd10 += $sp188cde;
        $sp15e80d = $sp1ca412 * $sp863814->cost;
        $sp6b06ff = \App\Order::unique_no();
        try {
            DB::transaction(function () use($sp863814, $sp6b06ff, $sp3c9138, $sp6c47af, $sp05a58e, $sp9a876f, $sp1ca412, $sp15e80d, $sp6fa047, $sp188cde, $sp5698b2, $sp50cd10, $sp2e6adb, $spd81ea0, $sp1ae43e) {
                if ($sp3c9138) {
                    $sp3c9138->status = \App\Coupon::STATUS_USED;
                    $sp3c9138->count_used++;
                    $sp3c9138->save();
                    $speb1f6b = '使用优惠券: ' . $sp3c9138->coupon;
                } else {
                    $speb1f6b = null;
                }
                $sp322370 = new \App\Order(array('user_id' => $sp863814->user_id, 'order_no' => $sp6b06ff, 'product_id' => $sp863814->id, 'product_name' => $sp863814->name, 'count' => $sp1ca412, 'ip' => Helper::getIP(), 'customer' => $sp9a876f, 'contact' => $sp6c47af, 'contact_ext' => $sp05a58e, 'cost' => $sp15e80d, 'price' => $sp6fa047, 'sms_price' => $sp188cde, 'discount' => $sp5698b2, 'paid' => $sp50cd10, 'pay_id' => $sp2e6adb->id, 'fee' => $spd81ea0, 'system_fee' => $spd81ea0, 'income' => $sp1ae43e, 'status' => \App\Order::STATUS_UNPAY, 'remark' => $speb1f6b, 'created_at' => Carbon::now()));
                $sp322370->saveOrFail();
            });
        } catch (\Throwable $sp45222f) {
            Log::error('Shop.Pay.buy 下单失败', array('exception' => $sp45222f));
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.pay.internal_error')));
        }
        if ($sp50cd10 === 0) { $this->shipOrder($sp13451b, $sp6b06ff, $sp50cd10, null);
            return redirect()->away(route('pay.result', array($sp6b06ff), false));
        }
        $sp656002 = $sp6b06ff;
        return $this->goPay($sp13451b, $sp6b06ff, $sp656002, $sp2e6adb, $sp50cd10);
    }
    function pay(Request $sp13451b, $sp6b06ff) { $sp322370 = \App\Order::whereOrderNo($sp6b06ff)->first();
    if ($sp322370 == null) {
        return self::renderResultPage($sp13451b, array('msg' => trans('shop.order.not_found')));
    }
    if ($sp322370->status !== \App\Order::STATUS_UNPAY) {
        return redirect('/pay/result/' . $sp6b06ff);
    }
    $spf6b1a9 = 'pay: ' . $sp322370->pay_id;
    $sp2e6adb = $sp322370->pay;
    if (!$sp2e6adb) {
        \Log::error($spf6b1a9 . ' cannot find Pay');
        return $this->renderResultPage($sp13451b, array('msg' => trans('shop.pay.not_found')));
    }
    $spf6b1a9 .= ',' . $sp2e6adb->driver;
    $sp0451dd = json_decode($sp2e6adb->config, true);
    $sp0451dd['payway'] = $sp2e6adb->way;
    $sp0451dd['out_trade_no'] = $sp6b06ff;
    try {
        $this->payApi = GatewayPay::getDriver($sp2e6adb);
    } catch (\Exception $sp45222f) {
        \Log::error($spf6b1a9 . ' cannot find Driver: ' . $sp45222f->getMessage());
        return $this->renderResultPage($sp13451b, array('msg' => trans('shop.pay.driver_not_found')));
    }
    if ($this->payApi->verify($sp0451dd, function ($sp6b06ff, $sp07bb5f, $spbe9054) use($sp13451b) {
        try {
            $this->shipOrder($sp13451b, $sp6b06ff, $sp07bb5f, $spbe9054);
        } catch (\Exception $sp45222f) {
            $this->renderResultPage($sp13451b, array('success' => false, 'msg' => $sp45222f->getMessage()));
        }
    })) {
        \Log::notice($spf6b1a9 . ' already success' . '');
        return redirect('/pay/result/' . $sp6b06ff);
    }
    if ($sp322370->created_at < Carbon::now()->addMinutes(-5)) {
        return $this->renderResultPage($sp13451b, array('msg' => trans('shop.order.expired')));
    }
    $sp863814 = Product::where('id', $sp322370->product_id)->where('enabled', 1)->first();
    if ($sp863814 == null) {
        return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.not_on_sell')));
    }
    $sp863814->setAttribute('count', count($sp863814->cards) ? $sp863814->cards[0]->count : 0);
    if ($sp863814->count < $sp322370->count) {
        return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.out_of_stock')));
    }
    $sp656002 = $sp6b06ff; return $this->goPay($sp13451b, $sp6b06ff, $sp656002, $sp2e6adb, $sp322370->paid);
    }
    function qrcode(Request $sp13451b, $sp6b06ff, $sp8e0745) {
        $sp322370 = \App\Order::whereOrderNo($sp6b06ff)->with('product')->first();
        if ($sp322370 == null) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.order.not_found')));
        }
        if ($sp322370->created_at < Carbon::now()->addMinutes(-5)) {
            return $this->renderResultPage($sp13451b, array('msg' => trans('shop.order.expired')));
        }
        if ($sp322370->product_id !== \App\Product::ID_API) {
            $sp863814 = $sp322370->product;
            if ($sp863814 == null) {
                return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.not_found')));
            }
            if ($sp863814->count < $sp322370->count) {
                return self::renderResultPage($sp13451b, array('msg' => trans('shop.product.out_of_stock')));
            }
        }
        if (strpos($sp8e0745, '..')) {
            return $this->msg(trans('shop.you_are_sb'));
        }
        return view('pay/' . $sp8e0745, array('pay_id' => $sp322370->pay_id, 'name' => $sp322370->product_id === \App\Product::ID_API ? $sp322370->api_out_no : $sp322370->product->name . ' x ' . $sp322370->count . '件', 'amount' => $sp322370->paid, 'qrcode' => $sp13451b->get('url'), 'id' => $sp6b06ff));
    }
    function qrQuery(Request $sp13451b, $sp56237f) {
        $spbdf836 = $sp13451b->input('id');
        if (isset($spbdf836[5])) {
            return self::payReturn($sp13451b, $sp56237f, $spbdf836);
        } else {
            return Response::fail('order_no error');
        }
    }
    function payReturn(Request $sp13451b, $sp56237f, $sp6b06ff = null) {
        $spf6b1a9 = 'payReturn: ' . $sp56237f;
        \Log::debug($spf6b1a9);
        $sp2e6adb = \App\Pay::where('id', $sp56237f)->first();
        if (!$sp2e6adb) {
            return $this->renderResultPage($sp13451b, array('success' => 0, 'msg' => trans('shop.pay.not_found')));
        }
        $spf6b1a9 .= ',' . $sp2e6adb->driver;
        if ($sp6b06ff && isset($sp6b06ff[5])) {
            $sp322370 = \App\Order::whereOrderNo($sp6b06ff)->firstOrFail();
            if ($sp322370 && ($sp322370->status === \App\Order::STATUS_PAID || $sp322370->status === \App\Order::STATUS_SUCCESS)) {
                \Log::notice($spf6b1a9 . ' already success' . '');
            if ($sp13451b->ajax()) {
                return self::renderResultPage($sp13451b, array('success' => 1, 'data' => '/pay/result/' . $sp6b06ff), array('order' => $sp322370));
            } else {
                return redirect('/pay/result/' . $sp6b06ff);
            }
            }
        }
        try {
            $this->payApi = GatewayPay::getDriver($sp2e6adb);
        } catch (\Exception $sp45222f) {
            \Log::error($spf6b1a9 . ' cannot find Driver: ' . $sp45222f->getMessage());
            return $this->renderResultPage($sp13451b, array('success' => 0, 'msg' => trans('shop.pay.driver_not_found')));
        }
        $sp0451dd = json_decode($sp2e6adb->config, true);
        $sp0451dd['out_trade_no'] = $sp6b06ff;
        $sp0451dd['payway'] = $sp2e6adb->way;
        Log::debug($spf6b1a9 . ' will verify');
        if ($this->payApi->verify($sp0451dd, function ($sp88d22f, $sp07bb5f, $spbe9054) use($sp13451b, $spf6b1a9, &$sp6b06ff) {
            $sp6b06ff = $sp88d22f;
            try {
                Log::debug($spf6b1a9 . " shipOrder start, order_no: {$sp6b06ff}, amount: {$sp07bb5f}, trade_no: {$spbe9054}");
                $this->shipOrder($sp13451b, $sp6b06ff, $sp07bb5f, $spbe9054);
                Log::debug($spf6b1a9 . ' shipOrder end, order_no: ' . $sp6b06ff);
            } catch (\Exception $sp45222f) {
                Log::error($spf6b1a9 . ' shipOrder Exception: ' . $sp45222f->getMessage(), array('exception' => $sp45222f));
            }
        })) {
            Log::debug($spf6b1a9 . ' verify finished: 1' . '');
            if ($sp13451b->ajax()) {
                return self::renderResultPage($sp13451b, array('success' => 1, 'data' => '/pay/result/' . $sp6b06ff));
            } else {
                return redirect('/pay/result/' . $sp6b06ff);
            }
        } else {
            Log::debug($spf6b1a9 . ' verify finished: 0' . '');
            return $this->renderResultPage($sp13451b, array('success' => 0, 'msg' => trans('shop.pay.verify_failed')));
        }
    }
    function payNotify(Request $sp13451b, $sp56237f) {
        $spf6b1a9 = 'payNotify pay_id: ' . $sp56237f; Log::debug($spf6b1a9);
        $sp2e6adb = \App\Pay::where('id', $sp56237f)->first();
        if (!$sp2e6adb) {
            Log::error($spf6b1a9 . ' cannot find PayModel'); echo 'fail'; die;
        }
        $spf6b1a9 .= ',' . $sp2e6adb->driver;
        try {
            $this->payApi = GatewayPay::getDriver($sp2e6adb);
        } catch (\Exception $sp45222f) {
            Log::error($spf6b1a9 . ' cannot find Driver: ' . $sp45222f->getMessage()); echo 'fail'; die;
        }
        $sp0451dd = json_decode($sp2e6adb->config, true);
        $sp0451dd['payway'] = $sp2e6adb->way;
        $sp0451dd['isNotify'] = true;
        Log::debug($spf6b1a9 . ' will verify');
        $sp00ac62 = $this->payApi->verify($sp0451dd, function ($sp6b06ff, $sp07bb5f, $spbe9054) use($sp13451b, $spf6b1a9) {
            try {
                Log::debug($spf6b1a9 . " shipOrder start, order_no: {$sp6b06ff}, amount: {$sp07bb5f}, trade_no: {$spbe9054}");
                $this->shipOrder($sp13451b, $sp6b06ff, $sp07bb5f, $spbe9054);
                Log::debug($spf6b1a9 . ' shipOrder end, order_no: ' . $sp6b06ff);
            } catch (\Exception $sp45222f) {
                Log::error($spf6b1a9 . ' shipOrder Exception: ' . $sp45222f->getMessage());
            }
        });
        Log::debug($spf6b1a9 . ' notify finished: ' . (int) $sp00ac62 . ''); die;
    }
    function result(Request $sp13451b, $sp6b06ff) {
        $sp322370 = \App\Order::where('order_no', $sp6b06ff)->first();
        if ($sp322370 == null) {
            return self::renderResultPage($sp13451b, array('msg' => trans('shop.order.not_found')));
        }
        if ($sp322370->status === \App\Order::STATUS_PAID) {
            $sp1b8529 = $sp322370->user->qq;
            if ($sp322370->product) {
                if ($sp322370->product->delivery === \App\Product::DELIVERY_MANUAL) {
                    $spfd1ead = trans('shop.order.msg_product_manual_please_wait');
                } else {
                    $spfd1ead = trans('shop.order.msg_product_out_of_stock_not_send');
                }
            } else {
                $spfd1ead = trans('shop.order.msg_product_deleted');
            }
            if ($sp1b8529) {
                $spfd1ead .= '<br><a href="http://wpa.qq.com/msgrd?v=3&uin=' . $sp1b8529 . '&site=qq&menu=yes" target="_blank">客服QQ:' . $sp1b8529 . '</a>';
            }
            return self::renderResultPage($sp13451b, array('success' => false, 'title' => trans('shop.order_is_paid'), 'msg' => $spfd1ead), array('order' => $sp322370));
        } elseif ($sp322370->status >= \App\Order::STATUS_SUCCESS) {
            return self::showOrderResult($sp13451b, $sp322370);
        }
        return self::renderResultPage($sp13451b, array('success' => false, 'msg' => $sp322370->remark ? trans('shop.order_process_failed_because', array('reason' => $sp322370->remark)) : trans('shop.order_process_failed_default')), array('order' => $sp322370));
    }
    function renderResultPage(Request $sp13451b, $sp11ac9a, $sp965141 = array()) {
        if ($sp13451b->ajax()) {
            if (@$sp11ac9a['success']) {
                return Response::success($sp11ac9a['data']);
            } else {
                return Response::fail('error', $sp11ac9a['msg']);
            }
        } else {
            return view('pay.result', array_merge(array('result' => $sp11ac9a, 'data' => $sp965141), $sp965141));
        }
    }
    function shipOrder($sp13451b, $sp6b06ff, $sp07bb5f, $spbe9054) {
        $sp322370 = \App\Order::whereOrderNo($sp6b06ff)->first();
        if ($sp322370 === null) {
            Log::error('shipOrder: No query results for model [App\\Order:' . $sp6b06ff . ',trade_no:' . $spbe9054 . ',amount:' . $sp07bb5f . ']. die(\'success\');'); die('success');
        }
        if ($sp322370->paid > $sp07bb5f) {
            Log::alert('shipOrder, price may error, order_no:' . $sp6b06ff . ', paid:' . $sp322370->paid . ', $amount get:' . $sp07bb5f);
            $sp322370->remark = '支付金额(' . sprintf('%0.2f', $sp07bb5f / 100) . ') 小于 订单金额(' . sprintf('%0.2f', $sp322370->paid / 100) . ')';
            $sp322370->save();
            throw new \Exception($sp322370->remark);
        }
        $sp863814 = null;
        if ($sp322370->status === \App\Order::STATUS_UNPAY) {
            Log::debug('shipOrder.first_process:' . $sp6b06ff);
            if (FundHelper::orderSuccess($sp322370->id, function ($spde9919) use($spbe9054, &$sp322370, &$sp863814) {
                $sp322370 = $spde9919;
                if ($sp322370->status !== \App\Order::STATUS_UNPAY) {
                    \Log::debug('Shop.Pay.shipOrder: .first_process:' . $sp322370->order_no . ' already processed! #2');
                    return false; } $sp863814 = $sp322370->product()->lockForUpdate()->firstOrFail();
                $sp322370->pay_trade_no = $spbe9054; $sp322370->paid_at = Carbon::now();
                if ($sp863814->delivery === \App\Product::DELIVERY_MANUAL) {
                    $sp322370->status = \App\Order::STATUS_PAID; $sp322370->send_status = \App\Order::SEND_STATUS_CARD_UN;
                    $sp322370->saveOrFail(); return true;
                }
                if ($sp863814->delivery === \App\Product::DELIVERY_API) {
                    $spa07a9b = $sp863814->createApiCards($sp322370);
                } else {
                    $spa07a9b = Card::where('product_id', $sp322370->product_id)->whereRaw('`count_sold`<`count_all`')->take($sp322370->count)->lockForUpdate()->get();
                }
                $spf0473c = false;
                if (count($spa07a9b) === $sp322370->count) {
                    $spf0473c = true;
                } else {
                    if (count($spa07a9b)) {
                        foreach ($spa07a9b as $spbcc049) {
                            if ($spbcc049->type === \App\Card::TYPE_REPEAT && $spbcc049->count >= $sp322370->count)
                            { $spa07a9b = array($spbcc049);
                            $spf0473c = true; break;
                            }
                        }
                    }
                }
                if ($spf0473c === false) {
                    Log::alert('Shop.Pay.shipOrder: 订单:' . $sp322370->order_no . ', 购买数量:' . $sp322370->count . ', 卡数量:' . count($spa07a9b) . ' 卡密不足(已支付 未发货)');
                    $sp322370->status = \App\Order::STATUS_PAID;
                    $sp322370->saveOrFail();
                    return true;
                } else {
                    $sp48b16e = array();
                    foreach ($spa07a9b as $spbcc049) {
                        $sp48b16e[] = $spbcc049->id;
                    }
                    $sp322370->cards()->attach($sp48b16e);
                    if (count($spa07a9b) === 1 && $spa07a9b[0]->type === \App\Card::TYPE_REPEAT) {
                        \App\Card::where('id', $sp48b16e[0])->update(array('status' => \App\Card::STATUS_SOLD, 'count_sold' => DB::raw('`count_sold`+' . $sp322370->count)));
                    } else {
                        \App\Card::whereIn('id', $sp48b16e)->update(array('status' => \App\Card::STATUS_SOLD, 'count_sold' => DB::raw('`count_sold`+1')));
                    }
                    $sp322370->status = \App\Order::STATUS_SUCCESS; $sp322370->saveOrFail();
                    $sp863814->count_sold += $sp322370->count; $sp863814->saveOrFail();
                    return FundHelper::ACTION_CONTINUE;
                }
            })) {
                if ($sp863814->count_warn > 0 && $sp863814->count < $sp863814->count_warn) {
                    try { Mail::to($sp322370->user->email)->Queue(new ProductCountWarn($sp863814, $sp863814->count));
                    } catch (\Throwable $sp45222f) {
                        LogHelper::setLogFile('mail');
                        Log::error('shipOrder.count_warn error', array('product_id' => $sp322370->product_id, 'email' => $sp322370->user->email, 'exception' => $sp45222f->getMessage()));
                        LogHelper::setLogFile('card');
                    }
                }
                if (System::_getInt('mail_send_order')) {
                    $sp7c700e = @json_decode($sp322370->contact_ext, true)['_mail'];
                    if ($sp7c700e) { $sp322370->sendEmail($sp7c700e);
                    }
                }
                if ($sp322370->status === \App\Order::STATUS_SUCCESS && System::_getInt('sms_send_order')) {
                    $sp7a4d87 = @json_decode($sp322370->contact_ext, true)['_mobile'];
                    if ($sp7a4d87) { $sp322370->sendSms($sp7a4d87);
                    }
                }
            } else {
                if ($sp322370->status !== \App\Order::STATUS_UNPAY) {

                } else {
                    Log::error('Pay.shipOrder.orderSuccess Failed.'); return FALSE;
                }
            }
        } else {
            Log::debug('Shop.Pay.shipOrder: .order_no:' . $sp322370->order_no . ' already processed! #1');
        }
        return FALSE;
    }
    private function showOrderResult($sp13451b, $sp322370) {
        return self::renderResultPage($sp13451b, array('success' => true, 'msg' => $sp322370->getSendMessage()), array('card_txt' => join('&#013;&#010;', $sp322370->getCardsArray()), 'order' => $sp322370, 'product' => $sp322370->product));
    }
}