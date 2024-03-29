<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
class ProductCountWarn extends Mailable {
    use Queueable, SerializesModels;
    public $tries = 3;
    public $timeout = 20;
    public $product = null;
    public $product_count = null;
    public function __construct($sp863814, $spcf484c) {
        $this->product = $sp863814;
        $this->product_count = $spcf484c;
    }
    public function build() {
        return $this->subject('您的商品库存不足-' . config('app.name'))->view('emails.product_count_warn');
    }
}