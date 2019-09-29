<?php


/*
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,GET');
header('Access-Control-Allow-Headers:x-requested-with,content-type'); 
*/

define("ROOTPATH",dirname(__FILE__).'/php/');
define("STATICPATH",dirname(__FILE__).'/static/');
//判断是不是https
$httppro = "http://";
if ($_SERVER["SERVER_PORT"] == 443) {
    $httppro = "https://";
}

//主站域名
define('MAIN_DOMAIN', $_SERVER['HTTP_HOST']);
define("IMAGE_URL", $httppro.MAIN_DOMAIN.'/static/image/');
define("RESURL", $httppro.MAIN_DOMAIN.'/xxoo/res/');
include_once ROOTPATH.'lib/i.php';
