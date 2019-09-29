<?php

function _autoload($className) {
    $class = str_replace('\\', '/', $className);
    $controllersFile = ROOTPATH . $class . '.php';
    if (is_file($controllersFile)) {
        require $controllersFile;
        return true;
    }
    return false;
}

function _error() {
    //
}

//begin 日志系统--------------------------------------------------------------------------

/*
 * 数据库配置已在 const 文件中定义,所以这里不需要了
 * 
  define("LOG_SYSTEM_DBHOST", "127.0.0.1");
  define("LOG_SYSTEM_DBUSER", "root");
  define("LOG_SYSTEM_DBPWD", "root");
  define("LOG_SYSTEM_DBNAME", "yserver");
  define("LOG_SYSTEM_DBPORT", 3306);
 * 
 */

/**
 * 写系统日志 add_system_log(array('name' => '给用户送券失败',  'val' => "原因：没有找到用户"));
 */
function add_system_log($param) {
    $link = mysqli_connect(LOG_SYSTEM_DBHOST, LOG_SYSTEM_DBUSER, LOG_SYSTEM_DBPWD, LOG_SYSTEM_DBNAME, LOG_SYSTEM_DBPORT);
    mysqli_query($link, "SET NAMES 'utf8'");
    //判断有没有表，没有就创建一个
    $res = mysqli_query($link, "desc `log_system`");
    if (!$res) {
        $sql = "CREATE TABLE `log_system` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL COMMENT '名称',
                `uri` varchar(244) NOT NULL,
                `val` text COMMENT '值或说明',
                `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ip` varchar(40) DEFAULT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统日志'";
        mysqli_query($link, $sql);
    }
    $data['name'] = @$param['name'] ?: '无';
    $data['uri'] = @$_SERVER['REQUEST_URI'];
    if (isset($param['val']) === false || empty($param['val'])) {
        return false;
    }
    $data['val'] = $param['val']; //$m->g($param['val']);
    //获得用户设备IP 
    $spbill_create_ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    if (!$spbill_create_ip) {
        $spbill_create_ip = getenv("REMOTE_ADDR");
    }
    $data['ip'] = $spbill_create_ip;
    mysqli_query($link, "insert into `log_system` (id,`name`,`uri`,`val`,`ip`) values (null,'{$data['name']}','{$data['uri']}','{$data['val']}','{$data['ip']}')");
    $insert_id = mysqli_insert_id($link);
    mysqli_close($link);
    return $insert_id;
}

/**
 * 添加接口访问日志
 */
function add_access_log() {
    $link = mysqli_connect(LOG_SYSTEM_DBHOST, LOG_SYSTEM_DBUSER, LOG_SYSTEM_DBPWD, LOG_SYSTEM_DBNAME, LOG_SYSTEM_DBPORT);
    mysqli_query($link, "SET NAMES 'utf8'");
    //判断有没有表，没有就创建一个
    $res = mysqli_query($link, "desc `log_access`");
    if (!$res) {
        $sql = "CREATE TABLE `log_access` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `fromip` varchar(66) DEFAULT NULL,
                `uri` varchar(244) NOT NULL,
                `param` text,
                `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='接口访问日志'";
        mysqli_query($link, $sql);
    }
    $data['fromip'] = filter_input(INPUT_SERVER, "REMOTE_ADDR");
    $data['uri'] = @$_SERVER['REQUEST_URI'];
    $data['param'] = json_encode($_REQUEST, 1);
    $sql = "insert into `log_access` (id,`fromip`,`uri`,`param`) values (null,'{$data['fromip']}','{$data['uri']}','{$data['param']}')";
    mysqli_query($link, $sql);
    mysqli_insert_id($link);
    mysqli_close($link);
}

//end 日志系统--------------------------------------------------------------------------

/**
 * 判断是否用手机或电脑浏览
 * @return boolean 如果是用手机或微信浏览则返回true，如果用电脑浏览则返回false
 */
function judgeMobileBrowse() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'MicroMessenger') || strpos($user_agent, 'Android') || strpos($user_agent, 'Android') || strpos($user_agent, 'iPhone')) {
        return true;
    } else {
        return false;
    }
}

function errmsg($content) {
    header("Content-type: text/html; charset=utf-8");
    echo "<html><head><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"></head><div style='margin:60px auto;text-align:center;color:#666;'><h3>$content</h3></div></html>";
    die;
}

/**
 * 返回JSON数据
 * @param type $param
 */
function ajax($param) {
    header('Access-Control-Allow-Origin:*');
    // 返回JSON数据格式到客户端 包含状态信息
    header('Content-Type:application/json; charset=utf-8');
    exit(json_encode($param, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
}

/**
 * 发送socket消息
 * @param type $send_socket_data    发送的数据
 * @param type $mch_id  商户ID
 * @return boolean
 */
function sendsocket($data = array()) {
    $ip = '127.0.0.1';
    $port = 4188;
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    //发送socket
    if ($socket < 0) {
        addlog(array('title' => '创建socket失败', 'action' => CONTROLLER . '/' . ACTION, 'detail' => '无法连接本地4188服务端'));
        return false;
    } else {
        $result = socket_connect($socket, $ip, $port);
        if ($result < 0) {
            addlog(array('title' => '创建socket失败', 'action' => CONTROLLER . '/' . ACTION, 'detail' => '无法连接本地4188服务端'));
            return false;
        } else {
            $in = json_encode($data);
            if (!socket_write($socket, $in, strlen($in))) {
                //echo "发送失败";
                addlog(array('title' => 'socket发送数据失败', 'action' => CONTROLLER . '/' . ACTION, 'detail' => '连接4188服务端后，无法发送数据'));
                return false;
            } else {
                //echo "发送成功";
                sleep(1);
                socket_close($socket);
                return true;
            }
        }
    }
}

/**
 * 上传图片 
 * @param type $file   表单的files ，填写：$_FILES['upfile']
 * @param type $path   图片存放的路径
 * @param type $newfilename 新生成图片的文件名
 * @return array 返回新的文件名和图片的在服务器上的全路径、图片扩展名 ,  $data['name'] 和 $data['path'] 、$data['type']。
 */
function uploadImage($file = null, $path = './image', $newfilename = null) {
    if (!empty($file) and is_uploaded_file($file['tmp_name'])) {
        $photo_types = array('image/jpg', 'image/jpeg', 'image/png', 'image/pjpeg', 'image/gif', 'image/x-png'); //定义上传格式
        $max_size = 1024000;    //上传照片大小限制,默认1M
//        $name = $file['name'];
//        $type = $file['type'];
//        $size = $file['size'];
        $photo_name = $file["tmp_name"];
//            $photo_size = getimagesize($photo_name);
        //检查文件大小
        if ($max_size < $file["size"]) {
            return 401;       //echo "<script>alert('对不起，文件超过规定大小!');history.go(-1);</script>";
        }

        /*
          Array(
          [name] => magazine-unlock-03-2.3.1301-_762A9A1FFAD5E4EEBB70C1365A2954A4.jpg
          [type] => application/octet-stream
          [tmp_name] => /tmp/phpDHZyyP
          [error] => 0
          [size] => 26336
          )
         * *
         */

        //检查文件类型
        if (!in_array($file["type"], $photo_types)) {
            //return 402;       //echo "<script>alert('对不起，文件类型不符!');history.go(-1);</script>";
        }
        //服务器存放图片的路径
        $photo_folder = $path . DIRECTORY_SEPARATOR;
        //  开始处理上传
        if (!file_exists($photo_folder)) {  //检查照片目录是否存在
            mkdir($photo_folder, 0770, true);  //mkdir("temp/sub, 0777, true);
        }
        $pinfo = pathinfo($file["name"]);
        $photo_type = $pinfo['extension']; //上传文件扩展名
        if (empty($photo_type)) {
            $photo_type = substr($file['name'], -4, 4);
        }
        $time = time();
        $newFilename = $time . "." . $photo_type;  //图片文件名
        //如果有新的文件名
        if (!empty($newfilename)):
            $newFilename = $newfilename . "." . $photo_type;  //图片文件名
        endif;
        $imagePath = $photo_folder . $newFilename; //原图文件名，这里是加了路径的 
        //移动文件
        if (!move_uploaded_file($photo_name, $imagePath)) {
            return 403; //echo "移动文件出错";
        }
        //如果出错了，则返回错误
        $data['name'] = $newFilename;
        $data['path'] = $imagePath;
        $data['type'] = $photo_type;
        return $data;
    } else {
        return 0;
    }
}

/**
 * 阿里短信发送接口
 * @param type $mobile
 * @return boolean|string|int
 */
function common_send_sms($mobile = null) {
    if (empty($mobile)) {
        return false;
    } else {
        $m = new \lib\model();
        $redis = new \lib\yredis();

        //短信发送功能 开启状态
        $close = 0;
        if ($close == 1) {
            return array('msg' => '短信发送功能已经关闭！');
        }

        $appkey = '23330065'; //阿里大鱼  APPKEY
        $secret = 'e356e4a7fa144d4109ff89f0fbd31969'; //阿里大鱼  AppSecret
        $SmsTemplateCode = 'SMS_113750011'; //阿里大鱼  短信模板 
        $SmsFreeSignName = '异新优'; //阿里大鱼  短信签名 
        $code = rand(110000, 999999);
        $_SESSION['yixinuSmsRegCode'] = md5($code);
        $SmsParam = array(
            'name' => (string) $code
        );

        //检测是否有缓存验证码
        $msgres = $redis->get("yserver_register_sendmsg_val_{$mobile}");
        if ($msgres) {
            return array('msg' => '上一条验证码还未失效，可以继续使用..请勿频繁发送短信验证码.');
        }

        //begin 检测5分钟内是否已经发送过了 
        $sendtime = $m->field("SELECT `time` FROM ` log_msg` WHERE `time` > now()-INTERVAL 5 minute and mobile='$mobile'");
        if ($sendtime) {
            return array('msg' => '发送太频繁，请5分钟后再试。');
        }
        //end 检测5分钟内是否已经发送过了
        //保存发送记录  start
        $data['mobile'] = $mobile;
        $data['val'] = $code;
        $m->save($data, 'log_msg');
        //保存发送记录  end
        //阿里大鱼短信发送   http://www.alidayu.com/
        include ROOTPATH . 'lib/taobaosdk/TopSdk.php';
        $c = new TopClient;
        $c->appkey = $appkey;
        $c->secretKey = $secret;
        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("");
        $req->setSmsType("normal");
        $req->setSmsFreeSignName($SmsFreeSignName);
        $req->setSmsParam(json_encode($SmsParam));
        $req->setRecNum((string) $mobile);
        $req->setSmsTemplateCode($SmsTemplateCode);
        $resp = $c->execute($req);

        $sendres = array();
        $sendres = json_decode(json_encode($resp), TRUE);
        if (isset($sendres['result'])) {
            $t = strtotime(date('Y-m-d', strtotime('+1 day'))) - time();    //距离24点的秒数
            $redis->setex("yserver_register_sendmsg_val_{$mobile}", 15 * 60, $code);    //验证码，15分钟内有效
            $redis->incr("yserver_register_sendmsg_num_{$mobile}");
            $redis->expireAt("yserver_register_sendmsg_num_{$mobile}", $t);
            return 1;
        } else {
            switch ($sendres['sub_code']) {
                case 'isv.BUSINESS_LIMIT_CONTROL': $sendres['msg'] = '发送太频繁或已经超限制，请稍后再试！';
                    break;
                case 'isv.MOBILE_NUMBER_ILLEGAL': $sendres['msg'] = '手机号码格式错误！';
                    break;
            }
            return $sendres;
        }
    }
}

function ispost() {
    if (filter_input(INPUT_SERVER, "REQUEST_METHOD") == 'POST') {
        return true;
    } else {
        return false;
    }
}

/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 20); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2); /* 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的 */
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /* curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}


/**
 * 
 * @param type $key  函数内已经加了REDIS前缀
 * @return boolean
 */
function get_config($key = null, $redis = null , $pre = null) {
    if (!empty($key)) {
        if(empty($pre)){
            $pre = REDIS_PRE;
        }
        $redis = $redis ? : new \lib\yredis();
        $value = $redis->get($pre . $key);
        if ($value == false || empty($value)) {
            $m = new core\model();
            $sql = "select `key`,`value` from `{$pre}config` where `key`='$key'";
            $cc = $m->find($sql);
            if ($cc) {
                $redis->setex($pre . $cc['key'], REDIS_TTL, $cc['value']);
            } else {
                return false;
            }

            $result = $redis->get($pre . $key);
            if ($result) {
                return $result;
            }
        } else {
            return $value;
        }
    }
    return false;
}
