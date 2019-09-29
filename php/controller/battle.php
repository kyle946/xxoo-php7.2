<?php

/*
 * 小游戏对战平台　房间操作类库.
 */

namespace controller;

/**
 * Description of battle
 *
 * @author kyle
 */
class battle extends com {

    protected $rkey = [
        'rooms' => 'game_' . REDIS_PRE . 'roomspecify_', //,初始有效时间 15 秒
        'roomr' => 'game_' . REDIS_PRE . 'roomrand_', //,初始有效时间 15 秒
        'userr' => 'game_' . REDIS_PRE . 'userroom_'      //用户当前加入的房间号,初始有效时间 15 秒
    ];

    /**
     * 自动匹配及加入房间
     * @param type $param
     */
    public function automatch($param) {
        $this->verifytoken($param);
        $token = $this->token;

//        //step 检测用户有没有创建或者加入房间，避免客户端重复请求会不断的创建新房间
//        $roomid = $this->redis->get($this->rkey['userr'] . $token['user_id']);
//        if (!$roomid) {
//            
//        }
        //如果没有创建或者加入，则创建新的 
        $roomid = $this->checkandjoin('rand', $token['user_id'], $token['token']);
        if (!$roomid) {
            ajax(['code' => 0, 'msg' => '房间不可用 ，加入失败']);
        } else {
            //房间人数是否已满，如果已满通知客户端 ，开始游戏
            $room_nums = $this->redis->hLen($this->rkey['roomr'] . $roomid);
            $start = 0;
            if ($room_nums == BATTLE_ROOMNUM) {
                $start = 1;
            }
            ajax(['code' => 0, 'msg' => 'ok', 'data' => ['roomid' => $roomid, 'roomtype' => 'rand', 'start' => $start]]);
        }
    }

    /**
     * 创建不进入随机分配的房间
     * @param type $param
     * @return string
     */
    public function createroom($param) {
        $this->verifytoken($param);
        $_token = $this->token;
        //
        $uid = $_token['user_id'];
        $token = $_token['token'];
        //生成房间、房间序号
        $roomid = $uid . rand(111, 999);
        $this->redis->hSet($this->rkey['rooms'] . $roomid, $uid, $token);
        //将用户加入到房间
        $this->redis->setex($this->rkey['userr'] . $uid, BATTLE_ROOM_TIMEOUT, $roomid);
        //缓存房间的时间
        $this->redis->expire($this->rkey['rooms'] . $roomid, BATTLE_ROOM_TIMEOUT);
        //return $roomid;
        ajax(['code' => 0, 'msg' => 'ok', 'data' => ['roomid' => $roomid, 'roomtype' => 'specify', 'start' => 0]]);
    }

    /**
     * 加入指定的房间
     * @param type $param
     */
    public function joinroom($param) {
        $this->verifytoken($param);
        $token = $this->token;
        if (isset($param['roomid']) == false || empty($param['roomid'])) {
            ajax(['code' => 10016, 'msg' => '没有传入房间号.']);
        }
        $res = $this->checkandjoin('specify', $token['user_id'], $token['token'], $param['roomid']);
        if (!$res) {
            ajax(['code' => 10008, 'msg' => '房间不可用 ，加入失败']);
        } else {
            ajax(['code' => 0, 'msg' => 'ok', 'data' => ['roomid' => $param['roomid'], 'roomtype' => 'specify', 'start' => 1]]);
        }
    }

    /**
     * 检查可用房间并加入
     * @param type $param
     */
    protected function checkandjoin($type, $uid, $token, $roomid = 0) {
        //随机
        if ($type == 'rand') {
            $roomid = 0;
            $redis_room_arr = $this->redis->keys($this->rkey['roomr'] . "*");
            if (is_array($redis_room_arr) && count($redis_room_arr) > 0) {
                $redis_room_item = end($redis_room_arr);
                $roomid_string = str_replace($this->rkey['roomr'], '', $redis_room_item);
                $roomid = intval($roomid_string);
            }

            $room_nums = $this->redis->hLen($this->rkey['roomr'] . $roomid);
            //如果有随机分配的房间号，并且房间能用，并且房间人数未满
            if ($roomid && $room_nums > 0 && $room_nums < BATTLE_ROOMNUM) {
                //将用户加入到房间
                $this->redis->hSet($this->rkey['roomr'] . $roomid, $uid, $token);
                $this->redis->setex($this->rkey['userr'] . $uid, BATTLE_ROOM_TIMEOUT, $roomid);
                //缓存房间的时间
                $this->redis->expire($this->rkey['roomr'] . $roomid, BATTLE_ROOM_TIMEOUT);
                return $roomid;
            } else {
                //生成房间、房间序号
                $roomid = $uid . rand(111, 999);
                //将用户加入到房间
                $this->redis->hSet($this->rkey['roomr'] . $roomid, $uid, $token);
                $this->redis->setex($this->rkey['userr'] . $uid, BATTLE_ROOM_TIMEOUT, $roomid);
                //缓存房间的时间
                $this->redis->expire($this->rkey['roomr'] . $roomid, BATTLE_ROOM_TIMEOUT);
                return $roomid;
            }
        }

        //指定
        else if ($type == 'specify') {
            $room_nums = $this->redis->hLen($this->rkey['rooms'] . $roomid);
            if ($roomid && $room_nums > 0 && $room_nums < BATTLE_ROOMNUM) {
                //将用户加入到房间
                $this->redis->hSet($this->rkey['rooms'] . $roomid, $uid, $token);
                $this->redis->setex($this->rkey['userr'] . $uid, BATTLE_ROOM_TIMEOUT, $roomid);
                //缓存房间的时间
                $this->redis->expire($this->rkey['rooms'] . $roomid, BATTLE_ROOM_TIMEOUT);
                return $roomid;
            } else {
                return 0;
            }
        }
    }

    /**
     * 获取房间内所有用户信息
     * @param type $param
     */
    public function alluser($param) {
        $this->verifytoken($param);
        $token = $this->token;
        if (empty($param['type'])) {
            ajax(['code' => 10008, 'msg' => '参数错误，或没有输入正确的参数.']);
        }
        if (empty($param['roomid'])) {
            ajax(['code' => 10008, 'msg' => '参数错误，或没有输入正确的参数.']);
        }

        $roomtype = $param['type'];
        $roomid = $param['roomid'];

        $rkey = '';
        if ($roomtype == 'rand') {
            $rkey = 'roomr';
        } else if ($roomtype == 'specify') {
            $rkey = 'rooms';
        }

        //
        $list_key = $this->redis->hKeys($this->rkey[$rkey] . $roomid);
        //$list_val = $this->redis->sort($this->rkey[$rkey], ['by' => $this->rkey[$rkey], 'get' => ['*']]);
        $ids = '';
        if ($list_key && count($list_key) > 0) {
            foreach ($list_key as $uid) {
                $uid = intval($uid);
                $ids = $ids . $uid . ',';
            }
            $ids = substr($ids, 0, -1);
            $sql = "select id,username,nickname,thumb from `{$this->m->prefix}user` where `id` in ($ids) ";
            $list = $this->m->select($sql);
            if ($list) {

                //找出是谁开的房间
                $host = intval(substr($roomid, 0, -3));
                if ($list[0]['id'] == $host) {
                    $list[0]['host'] = 1;
                    $list[1]['host'] = 0;
                } else {
                    $list[0]['host'] = 0;
                    $list[1]['host'] = 1;
                }

                //把用户自己排在前面
                $userlist = [];
                if ($list[0]['id'] == $this->token['user_id']) {
                    $userlist[0] = $list[0];
                    $userlist[1] = $list[1];
                } else {
                    $userlist[0] = $list[1];
                    $userlist[1] = $list[0];
                }
                ajax(['code' => 0, 'msg' => 'ok', 'data' => $userlist]);
            }
        }
        ajax(['code' => 1, 'msg' => '获取数据失败.']);
    }

    /**
     * 销毁房间
     * @param type $param
     */
    public function destroyroom($param) {
        $this->verifytoken($param);
        $token = $this->token;
        $roomid = $this->redis->get($this->rkey['userr'] . $token['user_id']);
        //找出是谁开的房间
        $host = intval(substr($roomid, 0, -3));
        //只能销毁自己开的房间
        if ($host == $token['user_id']) {
            $this->redis->del($this->rkey['roomr'] . $roomid);
            $this->redis->del($this->rkey['rooms'] . $roomid);
            $this->redis->del($this->rkey['userr'] . $token['user_id']);
            ajax(['code' => 0, 'msg' => 'ok']);
        } else {
            ajax(['code' => 1, 'msg' => 'destroy error.']);
        }
    }

    /**
     * 随机取一个机器人的头像和昵称
     * @param type $param
     */
    public function randuser($param) {
        $nickname_arr = require CACHE_PATH . 'nickname.php';
        $max = count($nickname_arr);
        $index = rand(1, $max);
        $data['nickname'] = $nickname_arr[$index];
        $data['avatar'] = RESURL . 'robot.png';
        ajax(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

}
