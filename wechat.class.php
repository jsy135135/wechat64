<?php

/**
 * @Author: jsy135135
 * @email:732677288@qq.com
 * @Date:   2018-03-08 14:50:37
 * @Last Modified by:   jsy135135
 * @Last Modified time: 2018-03-09 17:24:37
 */
// 引入配置文件
require './wechat.cfg.php';

class Wechat
{

    // 封装类成员
    // private  私有
    // public  公共
    // protected  受保护
    // 构造方法
    public function __construct()
    {
        //实列时会触发，进行相关参数的初始化操作
        $this->token = TOKEN;
        $this->appid = APPID;
        $this->appsecret = APPSECRET;
        // 模板
        $this->textTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>0</FuncFlag>
        </xml>";
    }
    // 类相关方法实现
    // 调用校验
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }
    // 消息管理
    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        // file_put_contents('./debug.txt', $postStr);
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            // 通过接收不同的数据类型，进行不同的方法处理
            switch ($postObj->MsgType) {
                // 调用文本处理方法
                case 'text':
                    $this->doText($postObj);
                    break;
                // 调用图片处理方法
                case 'image':
                    $this->doImage($postObj);
                    break;
                // 调用语音处理方法
                case 'voice':
                    $this->doVoice($postObj);
                    break;
                // 调用位置处理方法
                case 'location':
                    $this->doLocation($postObj);
                    break;
                // 调用事件处理方法
                case 'event':
                    $this->doEvent($postObj);
                    break;
                default:
                    break;
            }
        }
    }
    // 检查签名
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    // 文本消息处理
    private function doText($postObj)
    {
        $keyword = trim($postObj->Content);
        if (!empty($keyword)) {
            // 通过用户传输的不同的文本值，进行不同的回复
            $contentStr = "Welcome to wechat world!";
            if ($keyword === '你是谁') {
                $contentStr = '我是PHP学院的小秘书,小象';
            }
            // 接入自动回复机器人
            $content = file_get_contents('http://api.qingyunke.com/api.php?key=free&appid=0&msg='.$keyword);
            // 转json取数据
            $contentStr = json_decode($content)->content;
            // 替换换行
            $contentStr = str_replace('{br}', "\r", $contentStr);
            $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
            echo $resultStr;
        }
    }
    // 图片消息处理
    private function doImage($postObj)
    {
        // 获取图片地址
        $PicUrl = $postObj->PicUrl;
        // 以文本消息回复
        $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $PicUrl);
        // file_put_contents('./return.txt',$resultStr);
        echo $resultStr;
    }
    // 语音消息处理
    private function doVoice($postObj)
    {
        $contentStr = '您发送的语音已经接收到,MediaId:'.$postObj->MediaId;
        $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
        // file_put_contents('./return.txt',$resultStr);
        echo $resultStr;
    }
    // 位置消息处理
    private function doLocation($postObj)
    {
        // 返回用户经纬度信息
        $locationX = $postObj->Location_X;
        $locationY = $postObj->Location_Y;
        $contentStr = '您所在位置:经度'.$locationY.' 纬度'.$locationX;
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
        file_put_contents('location.txt',$resultStr);
        echo $resultStr;
    }
    // 封装请求方法
    // curl四步
    // 支持http、https协议，支持get和post请求方式
    public function request($url,$https=true,$method='get',$data=null)
    {
        // 1、初始化
        $ch = curl_init($url);
        // 2、配置
        // 返回数据以文件流形式
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        // 模拟为浏览器发送
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3218.0 Safari/537.36');
        // 支持https
        if($https === true)
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        // 支持post
        if($method === 'post')
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // 3、发送请求
        $content = curl_exec($ch);
        // 4、关闭资源
        curl_close($ch);
        // 返回数据
        return $content;
    }
    // 获取access_token
    public function getAccessToken()
    {
        // 判断换成是否有效
        $redis = new Redis();
        $redis->connect('127.0.0.1',6379);
        $access_token = $redis->get('access_token');
        // 没有或者过期，返回false
        if($access_token === false){
            // 1、url
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
            // 2、请求方式
            // 3、发送请求
            $content = $this->request($url);
            // 4、处理返回值
            $access_token = json_decode($content)->access_token;
            // 缓存数据
            $redis->set('access_token',$access_token);
            $redis->setTimeout('access_token',7000);
        }
        return $access_token;
    }
    // 获取ticket
    public function getTicket($tmp=true)
    {
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$this->getAccessToken();
        // 2、请求方式
        // 判断是生成临时还是永久的
        if($tmp === true){
           $data = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}';
        }else{
           $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}';
        }
        // 3、发送请求
        // request($url,$https=true,$method='get',$data=null)
        $content = $this->request($url,true,'post',$data);
        // var_dump($content);die();
        // 4、处理返回值
        $ticket = json_decode($content)->ticket;
        return $ticket;
    }
    // 通过ticket获取QRCode
    public function getQRCode()
    {
        // 1、url
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$this->getTicket();
        // 2、请求方式
        // 3、发送请求
        $content = $this->request($url);
        // 4、处理返回值
        header('Content-Type:image/jpg');
        echo $content;
        file_put_contents('./'.time().'_qrcode.jpg',$content);
    }
    // 事件消息处理
    public function doEvent($postObj)
    {
        // 不同的事件交由不同的方法处理
        switch ($postObj->Event) {
            case 'subscribe':
                // 关注事件和未关注扫描二维码事件
                $this->doSubscribe($postObj);
                break;
            case 'unsubscribe':
                // 取消关注事件
                $this->doUnsubscribe($postObj);
                break;
            case 'SCAN':
                // 已关注扫描二维码事件
                $this->doScan($postObj);
                break;
            case 'CLICK':
                // 自定义菜单点击事件
                $this->doClick($postObj);
                break;
            default:
                # code...
                break;
        }
    }
    // 未关注扫描二维码事件和关注事件
    public function doSubscribe($postObj)
    {
        // 判断用户是否扫描带场景值的二维码
        $contentStr = '欢迎加入！';
        if(!empty($postObj->EventKey)){
            $contentStr = '欢迎加入,您扫描的二维码场景值为'.$postObj->EventKey;
        }
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
        // file_put_contents('./test1.txt',$resultStr);
        echo $resultStr;
    }
    // 已关注扫描二维码事件
    public function doScan($postObj)
    {
        if($postObj->EventKey == 123){
            // 告知用户参加活动或者等等已经成功
            $contentStr = '您已经成功参加周年庆活动！';
            $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
            // file_put_contents('test2.txt',$resultStr);
            echo $resultStr;
        }
    }
    // 取消关注事件
    public function doUnsubscribe($postObj)
    {
        // 记录用户离开的时间和具体人
        // file_put_contents('./leave.txt',$postObj->FromUserName);
        $redis = new Redis();
        $redis->connect('127.0.0.1',6379);
        $data = array(
            'time' => $postObj->CreateTime,
            'opendID' => $postObj->FromUserName
        );
        // hash存储
        $redis->hMset($postObj->FromUserName,$data);
    }
    // 获取用户openID列表
    public function getUserList()
    {
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->getAccessToken();
        // 2、请求方式
        // 3、发送请求
        $content = $this->request($url);
        // 4、处理返回值
        $content = json_decode($content);
        // var_dump($content);die;
        foreach ($content->data->openid as $key => $value) {
            echo '<a href="http://localhost/wechat64/do.php?action=getUserInfo&openid='.$value.'">'.($key+1).'#'.$value.'</a><br />';
        }
    }
    // 通过用户openID获取用户基本信息
    public function getUserInfo($openid)
    {
        // $openid = 'oGMVlwxj68084lJIHOCflEh86Rig';
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openid.'&lang=zh_CN';
        // 2、请求方式
        // 3、发送请求
        $content = $this->request($url);
        // 4、处理返回值
        $content = json_decode($content);
        // 转换显示性别
        switch ($content->sex) {
            case '1':
                $sex = '男';
                break;
            case '2':
                $sex = '女';
                break;
            default:
                $sex = '未知';
                break;
        }
        // 简单显示用户信息
        echo '昵称:'.$content->nickname.'<br />';
        echo '性别:'.$sex.'<br />';
        echo '省份:'.$content->province.'<br />';
        echo '关注事件:'.date('Y-m-d H:i:s',$content->subscribe_time).'<br />';
        echo '<img src="'.$content->headimgurl.'" />';
    }
    // 上传素材
    public function uploadMedia()
    {
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->getAccessToken().'&type=image';
        // 2、请求方式
        // $data = array(
            // 'media' => '@D:/phpStudy/WWW/wechat64/image/head_2DgK_2934g204229.jpg',
        // );
        // php5.5以上要使用一下语法
        // $data = array(
        //     'media' => new CURLFile('D:/phpStudy/WWW/wechat64/image/head_2DgK_2934g204229.jpg'),
        // );
        // 3、发送请求
        $content = $this->request($url,true,'post',$data);
        // 4、处理返回值
        $content = json_decode($content);
        $mediaID = $content->media_id;
        echo $mediaID;
    }
    // 下载素材
    public function getMedia()
    {
        $media_id = 'pMEKCQ6z916JDzk9T6qsh_hsFGYE7zKHb6dOBKyGrR6XgTT2ALtWBGC4dBnebW1h';
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$media_id;
        // $url = 'http://img2.imgtn.bdimg.com/it/u=3588772980,2454248748&fm=27&gp=0.jpg';
        // 2、请求方式
        // 3、发送请求
        $content = $this->request($url,false);
        // var_dump($content);die;
        // 4、处理返回值
        echo file_put_contents('./1.gif',$content);
        // echo file_put_contents('./0.jpg',$content);
    }
    // 创建菜单
    public function createMenu()
    {
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccessToken();
        // 2、请求方式
        $data = '{
                 "button":[
                 {
                      "type":"click",
                      "name":"最新资讯",
                      "key":"news"
                  },
                  {
                       "name":"php64更多",
                       "sub_button":[
                       {
                           "type":"view",
                           "name":"搜索",
                           "url":"http://www.jd.com"
                        },
                        {
                           "type": "scancode_push",
                           "name": "扫码推事件",
                           "key": "rselfmenu_0_1",
                        }]
                   }]
                }';
        // 3、发送请求
        $content = $this->request($url,true,'post',$data);
        // 4、处理返回值
        $content = json_decode($content);
        if($content->errcode == 0){
            echo '创建成功！';
        }else{
            echo '创建失败!'.'<br />';
            echo '错误码:'.$content->errcode.'<br />';
            echo '错误信息:'.$content->errmsg;
        }
    }
    // 查询菜单
    public function showMenu()
    {
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$this->getAccessToken();
        // 2、请求方式
        // 3、发送请求
        $content = $this->request($url);
        // 4、处理返回值
        var_dump($content);
    }
    // 删除菜单
    public function delMenu()
    {
        // 1、url
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->getAccessToken();
        // 2、请求方式
        // 3、发送请求
        $content = $this->request($url);
        // 4、处理返回值
        $content = json_decode($content);
        if($content->errcode == 0){
            echo '删除成功！';
        }else{
            echo '删除失败!'.'<br />';
            echo '错误码:'.$content->errcode.'<br />';
            echo '错误信息:'.$content->errmsg;
        }
    }
}