<?php
namespace fky;
class WechatJssdk {
  private $appId;
  private $appSecret;

  public function __construct($params = array()) {
    if (!is_array($params) || !$params['appId'] || !$params['appSecret']) {
      die('appId和appSecret填写错误,请用数组方式填写参数');
    }
    $this->appId = $params['appId'];
    $this->appSecret = $params['appSecret'];
  }

  public function getSignPackage($path = '', $goUrl = null) {
    $jsapiTicket = $this->getJsApiTicket($path);

    if ($goUrl === null) {
      // 注意 URL 一定要动态获取，不能 hardcode.
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    } else {
      $url = $goUrl;
    }


    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "timestamp" => $timestamp,
      "nonceStr"  => $nonceStr,
      "signature" => $signature,
      
      "url"       => $url,
      "rawString" => $string
    );
    return $signPackage; 
  }

  public function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  public function getJsApiTicket($path = '') {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    if (empty($path)) {
      $filename = __DIR__."/../../wechatcache/jsapi_ticket.json";
    } else {
      $filename = $path.'/jsapi_ticket.json';
    }
    if (is_file($filename)) {
      $data = json_decode(file_get_contents($filename));
    } else {
      $data = json_decode(json_encode(array('jsapi_ticket'=>'','expire_time'=>0)));
    }
    if ($data->expire_time < time()) {
      $accessToken = $this->getAccessToken($path);
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $data->expire_time = time() + 7000;
        $data->jsapi_ticket = $ticket;
        $dir=dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir,0777);
        }
        $fp = fopen($filename, "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      } else {
        var_dump($res);
        die('ticket请求失败');
      }
    } else {
      $ticket = $data->jsapi_ticket;
    }

    return $ticket;
  }

  public function getAccessToken($path) {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    if (empty($path)) {
      $filename = __DIR__."/../../wechatcache/access_token.json";
    } else {
      $filename = $path.'/access_token.json';
    }
    if (is_file($filename)) {
      $data = json_decode(file_get_contents($filename));
    } else {
      $data = json_decode(json_encode(array('access_token'=>'','expire_time'=>0)));
    }
    if ($data->expire_time < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
        $dir=dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir,0777);
        }
        $fp = fopen($filename, "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      } else {
        var_dump($res);
        die('access_token请求失败');
      }
    } else {
      $access_token = $data->access_token;
    }
    return $access_token;
  }

  public function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}

