<?php

/*
 * .
 */

namespace controller;

/**
 * Description of user
 *
 * @author kyle
 */
class user extends com {

    /**
     * 游戏设置
     * @param type $param
     */
    public function set($param) {
        $this->verifytoken($param);
        $token = $this->token;
        $d = @$param['data'];
        $data['playmusic'] = $d['playmusic'];
        $data['autoadd'] = $d['autoadd'];
        $data['isaddfriend'] = $d['isaddfriend'];
        $data['challenge'] = $d['challenge'];
        $res = $this->m->save($data, "user", "u", "id={$token['user_id']}");
        if ($res) {
            ajax(['code' => 0, 'msg' => 'ok']);
        } else {
            ajax(['code' => 1, 'msg' => 'ok']);
        }
    }

    /**
     * 添加对方为好友
     * @param type $param
     */
    public function addfriend($param) {
        $this->verifytoken($param);
        $token = $this->token;
        $data['uid'] = $token['user_id'];
        $data['fid'] = @$param['uid'];
        $data['nickname'] = @$param['nickname'];
        $data['avatar'] = @$param['avatar'];
        //检测是否添加过
        $add = $this->m->find("select uid,fid from `{$this->m->prefix}friend` where uid={$token['user_id']} and fid={$data['fid']}");
        if ($add) {
            ajax(['code' => 1, 'msg' => '已经是好友关系']);
        }

        $res = $this->m->save($data, "friend");
        if ($res) {
            ajax(['code' => 0, 'msg' => 'ok']);
        } else {
            ajax(['code' => 1, 'msg' => 'ok']);
        }
    }

    /**
     * 获取自己的好友关系列表
     * @param type $param
     */
    public function getfriend($param) {
        $this->verifytoken($param);
        $token = $this->token;
        $sql = "select f.*,u.online from `{$this->m->prefix}friend` f "
                . " left join `{$this->m->prefix}user` u on f.fid=u.id  "
                . " where f.uid={$token['user_id']}";
        $flist = $this->m->select($sql);
        if ($flist) {
            ajax(['code' => 0, 'msg' => 'ok', 'data' => $flist]);
        } else {
            ajax(['code' => 1, 'msg' => 'ok']);
        }
    }

}
