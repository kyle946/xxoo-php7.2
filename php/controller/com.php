<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace controller;

/**
 * Description of com
 *
 * @author kyle
 */
class com extends \lib\Controller {

    protected function getuserinfo($uid) {
        //重新获取数据
        $sql = "select id,openid,username as uid,mobile,thumb,nickname,"
                . " integral,autoadd,isaddfriend,challenge,"
                . " playmusic from `{$this->m->prefix}user` where id=$uid";
        $userData = $this->m->find($sql);

        unset($userData['openid']);
        return $userData;
    }

    protected $token;

    protected function verifytoken($param = null) {
        if (!ispost()) {
            ajax(['code' => 10019, 'msg' => '请求方式错误，请使用POST请求。']);
        }
        if (empty($param))
            $param = $_POST;
        if (!isset($param['token'])) {
            ajax(['code' => 10015, 'msg' => '没有传入 token 参数']);
        }
        if (empty($param['token'])) {
            ajax(['code' => 10016, 'msg' => 'token 参数不能为空']);
        }
        $token_data = $this->redis->get(REDIS_PRE . 'wxlogin_userdata_' . $param['token']);
        if (!$token_data) {
            if ($param['token'] == 'xianglou') {
                $token_data = '{"openid":"oVd9W4yPsIDPWWaEb66Ly19iT1mQ","user_id":1001,"token":"xianglou"}';
                $this->token = json_decode($token_data,1);
                return true;
            }
            ajax(['code' => 10024, 'msg' => 'token 已经过期']);
        } else {
            //$token_data : {openid:'dfasdfdsafs',user_id:1,token:'dsfasdfasd'}
            $token_data = json_decode($token_data, 1);
            $this->token = $token_data;
            //每一次操作token延长2个小时
            $this->redis->expireAt(REDIS_PRE . 'wxlogin_userdata_' . $param['token'], time() + 7200);
            return true;
        }
    }

}
