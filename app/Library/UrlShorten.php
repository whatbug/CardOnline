<?php
namespace App\Library;
class UrlShorten {
    public static function shorten($sp5385ce, $sp66a10d = false) {
        if ($sp66a10d === false) {
            $sp66a10d = \App\System::_get('domain_shorten');
        }
        if ($sp66a10d === '1' || $sp66a10d === 'url.cn') {
            $sp0283c0 = UrlShorten::url_cn($sp5385ce);
        } elseif ($sp66a10d === '2' || $sp66a10d === 't.cn') {
            $sp0283c0 = UrlShorten::t_cn($sp5385ce);
        } elseif ($sp66a10d === 'w.url.cn') {
            $sp0283c0 = UrlShorten::w_url_cn($sp5385ce);
        } elseif ($sp66a10d === 'custom') {
            $sp0283c0 = UrlShorten::custom($sp5385ce);
        } else {
            return $sp5385ce;
        }
    }
    public static function t_cn_official($sp5385ce) {
        $sp5385ce = urlencode($sp5385ce);
        $sped3fae = '2590114856';
        $spbcd24c = 'http://api.t.sina.com.cn/short_url/shorten.json?source=' . $sped3fae . '&url_long=' . $sp5385ce;
        $sp12fdf7 =
            curl_init();
            curl_setopt($sp12fdf7, CURLOPT_URL, $spbcd24c);
            curl_setopt($sp12fdf7, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($sp12fdf7, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($sp12fdf7, CURLOPT_HEADER, 0);
            curl_setopt($sp12fdf7, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            $spfae0a4 = curl_exec($sp12fdf7);
            curl_close($sp12fdf7);
            $sp7ba407 = json_decode($spfae0a4, true);
            return isset($sp7ba407['url_short']) && strstr($sp7ba407['url_short'], 'http://') ? $sp7ba407['url_short'] : null; }
            public static function t_cn($sp5385ce) {
                $sp5385ce = urlencode($sp5385ce);
                $spbcd24c = 'https://i.alapi.cn/url/?url=' . $sp5385ce;
                $sp12fdf7 =
                    curl_init();
                    curl_setopt($sp12fdf7, CURLOPT_URL, $spbcd24c);
                    curl_setopt($sp12fdf7, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($sp12fdf7, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($sp12fdf7, CURLOPT_HEADER, 0);
                    curl_setopt($sp12fdf7, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                    $spfae0a4 =
                        curl_exec($sp12fdf7);
                        curl_close($sp12fdf7);
                    $sp7ba407 = json_decode($spfae0a4, true);
                    return isset($sp7ba407['shortUrl']) && strstr($sp7ba407['shortUrl'], 'http') ? $sp7ba407['shortUrl'] : null;
    }
    public static function url_cn($sp5385ce) {
        $sp5385ce = urlencode($sp5385ce);
        $spbcd24c = 'https://api.uomg.com/api/long2dwz?dwzapi=urlcn&url=' . $sp5385ce;
        $sp12fdf7 = curl_init(); curl_setopt($sp12fdf7, CURLOPT_URL, $spbcd24c);
        curl_setopt($sp12fdf7, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($sp12fdf7, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($sp12fdf7, CURLOPT_HEADER, 0);
        curl_setopt($sp12fdf7, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($sp12fdf7, CURLOPT_TIMEOUT, 5);
        curl_setopt($sp12fdf7, CURLOPT_CONNECTTIMEOUT, 5);
        $spfae0a4 = curl_exec($sp12fdf7); curl_close($sp12fdf7);
        $sp7ba407 = json_decode($spfae0a4, true);
        return isset($sp7ba407['ae_url']) && strstr($sp7ba407['ae_url'], 'http') ? $sp7ba407['ae_url'] : null; }
        public static function w_url_cn($sp5385ce) {
           return null;
        }
        public static function custom($sp5385ce) {
        $spbe0742 = ''; $sp4cbd3b = ''; $sp5385ce = urlencode($sp5385ce);
        $spbcd24c = 'http://api.his.cat/api/url/shorten.json?id=' . $spbe0742 . '&key=' . $sp4cbd3b . '&url=' . $sp5385ce;
        $sp12fdf7 =
            curl_init();
            curl_setopt($sp12fdf7, CURLOPT_URL, $spbcd24c);
            curl_setopt($sp12fdf7, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($sp12fdf7, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($sp12fdf7, CURLOPT_HEADER, 0);
            curl_setopt($sp12fdf7, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt($sp12fdf7, CURLOPT_TIMEOUT, 5);
            curl_setopt($sp12fdf7, CURLOPT_CONNECTTIMEOUT, 5);
            $spfae0a4 = curl_exec($sp12fdf7); curl_close($sp12fdf7);
            $sp7ba407 = json_decode($spfae0a4, true);
            return isset($sp7ba407['data']) && isset($sp7ba407['data']['short_url']) && strstr($sp7ba407['data']['short_url'], 'http') ? $sp7ba407['data']['short_url'] : null;
    }
}