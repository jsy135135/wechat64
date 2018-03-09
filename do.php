<?php

/**
 * @Author: jsy135135
 * @email:732677288@qq.com
 * @Date:   2018-03-09 10:46:06
 * @Last Modified by:   jsy135135
 * @Last Modified time: 2018-03-09 16:03:06
 */
header('Content-Type:text/html;charset=utf8');
$action = $_GET['action'];
require './wechat.class.php';
$wechat = new Wechat();
// 注意openid有没有
if(!empty($_GET['openid'])){
  $openid = $_GET['openid'];
}
// 获取access_token
// $wechat->getAccessToken();
// 获取ticket
// $wechat->getTicket();
// 获取二维码
// $wechat->getQRCode();
if($action == 'getUserInfo'){
  $wechat->$action($openid);
}else{
  $wechat->$action();
}

