<?php

/**
 * @Author: jsy135135
 * @email:732677288@qq.com
 * @Date:   2018-03-08 14:50:56
 * @Last Modified by:   jsy135135
 * @Last Modified time: 2018-03-08 15:09:18
 */
// wechat项目的入口文件
// 引入类文件
require './wechat.class.php';
$wechat = new Wechat();
// 判断是校验还是进行消息发送
if($_GET['echostr']){
  // 校验
  $wechat->valid();
}else{
  // 消息管理
  $wechat->responseMsg();
}
