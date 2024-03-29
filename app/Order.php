<?php
namespace App; use App\Jobs\OrderSms;
use App\Library\LogHelper;
use App\Mail\OrderShipped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log as LogWriter;
class Order extends Model {
    protected $guarded = array();
    const STATUS_UNPAY = 0;
    const STATUS_PAID = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FROZEN = 3;
    const STATUS_REFUND = 4;
    const STATUS = array(0 => '未支付', 1 => '未发货', 2 => '已发货', 3 => '已冻结', 4 => '已退款');
    const SEND_STATUS_UN = 0;
    const SEND_STATUS_EMAIL_SUCCESS = 1;
    const SEND_STATUS_EMAIL_FAILED = 2;
    const SEND_STATUS_MOBILE_SUCCESS = 3;
    const SEND_STATUS_MOBILE_FAILED = 4;
    const SEND_STATUS_CARD_UN = 100;
    const SEND_STATUS_CARD_PROCESSING = 101;
    const SEND_STATUS_CARD_SUCCESS = 102;
    const SEND_STATUS_CARD_FAILED = 103;
    protected $casts = array('api_info' => 'array');
    public static function unique_no() {
        $sp6b06ff = date('YmdHis') . str_random(5);
        while (\App\Order::where('order_no', $sp6b06ff)->exists()) {

            $sp6b06ff = date('YmdHis') . str_random(5);
        } return $sp6b06ff;
    }

        function user() {
        return $this->belongsTo(User::class);
    } function product() {
        return $this->belongsTo(Product::class);
    } function pay() {
        return $this->belongsTo(Pay::class);
    } function cards() {
        $sp5ae07f = $this->belongsToMany(Card::class);
        return $sp5ae07f->withTrashed();
    } function card_orders() {
        return $this->hasMany(CardOrder::class);
    } function fundRecord() {
        return $this->hasMany(FundRecord::class);
    } function getCardsArray() {
        $spa07a9b = array();
        $this->cards->each(function ($spbcc049) use(&$spa07a9b) {
            $spa07a9b[] = $spbcc049->card;
        });
        return $spa07a9b; }
        function getSendMessage() {
        if (count($this->cards)) {
            if (count($this->cards) == $this->count) {
                $sp228ba6 = '订单#' . $this->order_no . '&nbsp;已支付，您购买的内容如下：';
            } else {
                if ($this->cards[0]->type === \App\Card::TYPE_REPEAT || @$this->product->delivery === \App\Product::DELIVERY_MANUAL) {
                    $sp228ba6 = '订单#' . $this->order_no . '&nbsp;已支付，您购买的内容如下：';
                } else {
                    $sp228ba6 = '订单#' . $this->order_no . '&nbsp;已支付，目前库存不足，您还有' . ($this->count - count($this->cards)) . '件未发货，请联系商家客服发货，'; $sp228ba6 .= '商家客服QQ：<a href="http://wpa.qq.com/msgrd?v=3&uin=' . $this->user->qq . '&site=qq&menu=yes" target="_blank">' . $this->user->qq . '</a><br>'; $sp228ba6 .= '已发货商品见下方：'; } } } else { $sp228ba6 = '订单#' . $this->order_no . '&nbsp;已支付，目前库存不足，您购买的' . ($this->count - count($this->cards)) . '件未发货，请联系商家客服发货<br>'; $sp228ba6 .= '商家客服QQ：<a href="http://wpa.qq.com/msgrd?v=3&uin=' . $this->user->qq . '&site=qq&menu=yes" target="_blank">' . $this->user->qq . '</a>'; } return $sp228ba6; } function sendEmail($sp7c700e = false) { if ($sp7c700e === false) { $sp7c700e = @json_decode($this->contact_ext)['_mail']; } if (!$sp7c700e || !@filter_var($sp7c700e, FILTER_VALIDATE_EMAIL)) { return; } $spa07a9b = $this->getCardsArray(); try { Mail::to($sp7c700e)->Queue(new OrderShipped($this, $this->getSendMessage(), join('<br>', $spa07a9b))); $this->send_status = \App\Order::SEND_STATUS_EMAIL_SUCCESS; $this->saveOrFail(); } catch (\Throwable $sp45222f) { $this->send_status = \App\Order::SEND_STATUS_EMAIL_FAILED; $this->saveOrFail(); LogHelper::setLogFile('mail'); LogWriter::error('Order.sendEmail error', array('order_no' => $this->order_no, 'email' => $sp7c700e, 'cards' => $spa07a9b, 'exception' => $sp45222f->getMessage())); LogHelper::setLogFile('card'); } } function sendSms($sp7a4d87 = false) { if ($sp7a4d87 === false) { $sp7a4d87 = @json_decode($this->contact_ext)['_mobile']; } if (!$sp7a4d87 || strlen($sp7a4d87) !== 11) { return; } OrderSms::dispatch($sp7a4d87, $this); } }