<?php

namespace controller;

class home extends \lib\Controller {
    public function index($param) {
//         $this->show("test");
    }
    
    /**
     * 将字符串转换成二维码
     * @param type $param
     */
    public function paycode_qr($param) {
        $paycodestring = base64_decode($param['val']);
        include ROOTPATH.'lib/phpqrcode.php';
        \QRcode::png($paycodestring, false, QR_ECLEVEL_L, 8, 1);
    }
}
