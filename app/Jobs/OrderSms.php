<?php
namespace App\Jobs;
use App\Library\CurlRequest;
use App\System;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
class OrderSms implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $mobile = null;
    private $order = null;
    public function __construct($sp7a4d87, $sp322370) {
        $this->mobile = $sp7a4d87; $this->order = $sp322370;
    }
    public function handle() {
        CurlRequest::post('https://api.his.cat/api/sms/send', http_build_query(array('id' => System::_get('sms_api_id'), 'key' => System::_get('sms_api_key'), 'to' => $this->mobile, 'msg' => config('app.url') . '/pay/result/' . $this->order->order_no)));
    }
}