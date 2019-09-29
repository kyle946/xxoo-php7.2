<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace controller;

/**
 * Description of login
 *
 * @author kyle
 */
class login extends com {

    public function index($param) {
        if (!ispost()) {
            ajax(['code' => 10019, 'msg' => '请求方式错误，请使用POST请求。']);
        }
        if (!isset($param['code'])) {
            ajax(['code' => 10015, 'msg' => '没有传入code参数']);
        }
        if (empty($param['code'])) {
            ajax(['code' => 10016, 'msg' => 'code参数不能为空']);
        }
        $platform = "wx";
        if (!empty($param['platform']) && isset($param['platform'])) {
            $platform = $param['platform'];
        }

        //是否要访问微信接口和字节跳动接口
        $access_login_api = true;
        $loginInfo = [];
        if (!empty($param['token'])) {
            $tokendata = $this->redis->get(REDIS_PRE . 'wxlogin_userdata_' . $param['token']);
            if ($tokendata) {
                $loginInfo = json_decode($tokendata, 1);
                $access_login_api = false;
            }
        }
        
        
        if ($access_login_api) {
            
            if ($platform == "wx") {
                $url = sprintf('https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code', WX_APPID, WX_SECRET, $param['code']);
                $wxResponse = json_decode(httpRequest($url, 'GET'));
                if (!empty($wxResponse->errcode)) {
                    if ($wxResponse->errcode != 0) {
                        //添加系统日志
                        add_system_log(array('name' => 'wx.login 根据微信code换取code2Session失败', 'action' => ACTION, 'controller' => CONTROLLER, 'val' => "原因：" . $wxResponse->errmsg ));
                        ajax(['code' => 10033, 'msg' => $wxResponse->errmsg]);
                    }
                }
                if (empty($wxResponse->openid) || empty($wxResponse->session_key)) {
                    ajax(['code' => 20012, 'msg' => '登录失败']);
                }
                
                //$loginInfo : {openid:'dfasdfdsafs',user_id:1,token:'dsfasdfasd'}
                $loginInfo = ['openid' => $wxResponse->openid, 'sessionKey' => $wxResponse->session_key];
                $loginInfo['token'] = sha1($loginInfo['openid'] . date('Ymd').'kyle');
            }
        }
        
        
        //查询用户有没有注册过
        $userData = $this->m->find("select id,openid from `{$this->m->prefix}user` where openid='{$loginInfo['openid']}'");
        $reg = 0;   //默认没有注册过
        if ($userData) {
            //如果传入了头像和昵称，更新数据
            if (!empty($param['nickName'])) {
                $s_data['nickname'] = $param['nickName'];
                $s_data['thumb'] = $param['avatarUrl'];
                $this->m->save($s_data, "user", 'u', "id=$userData[id]");
                $userData['nickname'] = $param['nickName'];
                $userData['thumb'] = $param['avatarUrl'];
            }
            $loginInfo['user_id'] = $userData['id'];
            $reg = 1;   //用户已经注册
        } else {
            $s_data['openid'] = $loginInfo['openid'];
            $s_data['reg_time'] = date("Y-m-d H:i:s");
            $s_data['cur_login_time'] = 'now()---';
            $s_data['status'] = 1;
            $s_data['username'] = time() - ORDER_ID_START . rand(100, 999);
            $s_data['platform'] = $platform;
            if (!empty($param['nickName'])){
                $s_data['nickname'] = $param['nickName'];
            }
            if (!empty($param['avatarUrl'])){
                $s_data['thumb'] = $param['avatarUrl'];
            }
            //添加数据
            $loginInfo['user_id'] = $this->m->save($s_data, "user");
            
        }
        
        //$loginInfo : {openid:'dfasdfdsafs',user_id:1,token:'dsfasdfasd'}
        $this->redis->setex(REDIS_PRE . 'wxlogin_userdata_' . $loginInfo['token'], 7200, json_encode($loginInfo));
        
        $return_userinfo = $this->getuserinfo($loginInfo['user_id']);
        $return_userinfo['token'] = $loginInfo['token'];
        ajax(["code" => 0, "msg" => "success", 'data' => $return_userinfo ]);
    }
    
    
    /**
     * 更新头像
     * 
     * @param type $param
     */
    public function updateAvatar($param) {
        $this->verifytoken($param);
        $token = $this->token;
        if (empty(@$param['nickName'])) {
            ajax(array('code' => 10016, 'msg' => 'nickName 参数不能为空'));
        }
        if (empty(@$param['avatarUrl'])) {
            ajax(array('code' => 10016, 'msg' => 'avatarUrl 参数不能为空'));
        }
        $s_data['nickname'] = $param['nickName'];
        $s_data['thumb'] = $param['avatarUrl'];
        //添加数据
        $r = $this->m->save($s_data, "user", 'u', "openid='{$token['openid']}'");
        
        if($r){
            ajax(['code' => 0, 'msg' => 'success']);
        }
        ajax(['code' => 1, 'msg' => 'error']);
    }

}
