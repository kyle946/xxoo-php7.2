<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * 下面这几个常量名称不能修改,因为在 function.php 的 add_system_log 方法中也需要用到
 * 如果必须修改,请将 add_system_log 一起改掉
 */
define("LOG_SYSTEM_DBHOST", "127.0.0.1");
define("LOG_SYSTEM_DBUSER", " ");
define("LOG_SYSTEM_DBPWD", " ");
define("LOG_SYSTEM_DBNAME", " ");
define("LOG_SYSTEM_DBPORT", 3306);

define('REDIS_PRE',  'xo_');    //redis  前缀
define('SQLPRE',  'xo_');    //数据库  前缀
defined('REDIS_UNIXSOCKET') or define('REDIS_UNIXSOCKET',  '/var/run/redis/redis.sock');

define('BATTLE_ROOMNUM',2); //每个房间最多人数
define('BATTLE_ROOM_TIMEOUT',10); //房间初始有效时间(秒)

define('ORDER_ID_START',1560900000);
define('WX_APPID',  ' ');  
define('WX_SECRET',  ' '); 
