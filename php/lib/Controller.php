<?php

/**
 * Description of Controller
 *
 * @author  Kyle 青竹丹枫 <316686606@qq.com>
 */

namespace lib;
class Controller {
    
    protected $view = null;
    protected $redis = null;
    protected $m = null;



    public function __construct() {
        $this->view = new view();
        $this->redis = new yredis();
        $this->m = new model();
    }

    /**
     * 模板变量赋值
     * @param type $name
     * @param type $value
     */
    public function assign($name,$value = '') {
        $this->view->assign($name, $value);
    }
    //准备弃用
    private function display($templateFile = '') {
        if(empty($templateFile)){
            $templateFile = ACTION_NAME;
        }
        $this->view->display($templateFile);
    }
    //框架改版升级后用这个方法
    public function show($templateFile = '') {
        if(empty($templateFile)){
            $templateFile = ACTION_NAME;
        }
        $this->view->display($templateFile);
    }
    
    /**
     * 经过  strip_tags 和 trim 处理后的post变量 
     * @param type $var
     * @return type
     */
    public function _post($var) {
        if( !isset($_POST[$var]) ){
            return false;
        }
        return strip_tags(trim($_POST[$var]));
    }
    
    /**
     * 经过  strip_tags 和 trim 处理后的get变量 
     * @param type $var
     * @return type
     */
    public function _get($var) {
        if( !isset($_GET[$var]) ){
            return false;
        }
        return strip_tags(trim($_GET[$var]));
    }
    
    //开启伪静态下的get
    public function rget($var = null) {
        return \core\url::_get($var);
    }
    
    public function _empty() {
//        throw new \Exception('访问方式错误，请确认链接地址是否正确！');
        msg("访问方式错误，请确认链接地址是否正确！ 1002");
    }
}
