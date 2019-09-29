<?php

session_start();
ini_set('display_errors', 1);
header('X-Powered-By: 316686606@qq.com weixin:xianglou');
date_default_timezone_set("Asia/Shanghai");

define("CONTROLLER_PATH", ROOTPATH . '/controller/');
define("VIEW_PATH", ROOTPATH . '/view/');
define("CACHE_PATH", ROOTPATH . '/cache/');

include_once ROOTPATH . 'const.php';
include_once ROOTPATH . 'lib/Controller.php';
include_once ROOTPATH . 'lib/view.php';
include_once ROOTPATH . 'lib/DbMysql.php';
include_once ROOTPATH . "lib/model.php";
include_once ROOTPATH . "lib/yredis.php";
include_once ROOTPATH . "lib/function.php";

$_r = filter_input(INPUT_GET, "s");
$r = trim($_r, '/');
$controller = 'home';
$action = 'index';
if (!empty($r)) {
    if (strpos($r, "/") !== false) {
        list($controller, $action) = explode('/', $r);
    } else {
        $controller = $r;
    }
    if (empty($action)) {
        $action = 'index';
    }
}
define("CONTROLLER", $controller);
define("ACTION", $action);


// 设定错误和异常处理
//register_shutdown_function("_error");
//set_error_handler("_error");
//set_exception_handler("_error");
//注册自动加载类方法
spl_autoload_register('_autoload');


$class_file = '\\controller\\' . CONTROLLER;
if (!class_exists($class_file)) {
    errmsg("访问方式错误 ，类文件不存在！");
}
$class_ = new $class_file;
$class = new \ReflectionClass($class_file);
if ($class->hasMethod(ACTION)) {
    $before = $class->getMethod(ACTION);
    if ($before->isPublic()) {
        $arr = $_REQUEST;
        if (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] == 'application/json') {
            $string = file_get_contents('php://input');
            $arr = json_decode($string, 1);
        }
        $before->invoke($class_, $arr);
    }
} else {
    errmsg("访问方式错误，方法不存在");
}


