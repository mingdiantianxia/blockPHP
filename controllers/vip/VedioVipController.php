<?php
namespace controllers\vip;
use fky\traits\BaseTool;
/**
* \VedioVipController
*/
class VedioVipController extends \controllers\BaseController
{
	use BaseTool;
  
  public function Vip()
  {
	   @session_start();
     $token = uniqid();
     $_SESSION['Myvip_token'] = ['expiration'=>1,'token'=>$token,'time'=>time()];
     echo loadc('template')->make('vip/vip_start', ['token' => $token])->render();
  }

  public function Getvip()
  {
    if ($this->GPC('token')) {
        @session_start();
        $myvip_token = $_SESSION['Myvip_token'];
        if ($myvip_token['token'] != $this->GPC('token')) {
            die("<h1 style='text-align: center;'>无效访问！</h1>");
        }
        elseif ((time()-$myvip_token['time']) > $myvip_token['expiration']) {
            die("<h1 style='text-align: center;'>无效访问！！</h1>");
        }
    } else {
        die("<h1 style='text-align: center;'>无效访问！！！</h1>");
    }
     echo loadc('template')->make('vip/vip', [])->render();
    
  }

public function GetVideoList()
  {    
    header('Access-Control-Allow-Origin:*');
    date_default_timezone_set('PRC');

    $vip_url = $this->GPC('url');
    if (empty($vip_url)) {
        return false;
    }

    $url_type = 0;
    $request_type = 1;
    //腾讯视频
    if (stripos($vip_url,'//v.qq.com') !== false) {
        $url_type = 1;
    }
    //优酷视频
    if (stripos($vip_url,'youku.com') !== false) {
        $url_type = 2;
    //    $vip_url = 'http://fankongyuan.com/videolist/video_list.php?url='.$vip_url;
    }

    //爱奇艺视频
    if (stripos($vip_url,'iqiyi.com') !== false) {
        $url_type = 3;
    }
    //bilibili视频
    if (stripos($vip_url,'bilibili.com') !== false) {
        $url_type = 4;
    }

    if ($request_type == 1) {
        $response_data = $this->myHttpClient($vip_url);
    }
    elseif ($request_type == 2) {
        $response_data[1] = file_get_contents($vip_url);
    }

    $vedio_list = array();
    if ($url_type == 1) {
        preg_match('/var COVER_INFO = \{[\s\S]*"\}\}/',$response_data[1],$matches1);
        $video_json = explode('=',$matches1[0],2);
        $vidie_info = json_decode($video_json[1],true);

        $video_type = 0;//1电视剧，2电影
        if ($vidie_info['type_name'] == '电视剧') {
            $video_type = 1;
        }

        if ($video_type = 1) {

        }

        $base_url = 'https://v.qq.com/x/cover/';
        $vedio_list = array();
        foreach ($vidie_info['nomal_ids'] as $key => $item) {
            if (!in_array($item['F'],[2,7])) {
                continue;
            }
            $vedio_list_temp['num'] = $item['E'];
            $vedio_list_temp['url'] = $base_url. $vidie_info['id'].'/'.$item['V'].'.html';
            $vedio_list[] = $vedio_list_temp;
        }

    //    $video_type = 1;//1电视剧，2电影
    //    if (stripos($response_data[1],'<div class="mod_episode" data-tpl="episode" _wind="columnname=选集">') === false) {
    //        $video_type = 2;
    //    }
    //
    //    if ($video_type == 2) {
    //        //电影
    //        preg_match('/<a class="figure_detail" href="\/([\S]+\/)+[\S]+.html" r-on=/',$response_data[1],$matches1);
    //    }
    //    else {
    //        //电视剧
    //        preg_match('/<a href="\/detail\/([\S]+\/)+[\S]+.html" target="_blank" _stat="videolist:title">[\s\S]+<\/a>/',$response_data[1],$matches1);
    //    }
    //
    //    if ($video_type == 1) {
    //        $url = explode('<a href="',$matches1[0],2);
    //        $url = explode('" target=',$url[1],2);
    //        $response_data = myHttpClient('https://v.qq.com'.$url[0]);
    //        preg_match_all('/htt(p|ps):\/\/v.qq.com\/([\S]*\/)*[\S]*.html\?vid=[\S]*/',$response_data[1],$matches2);
    //
    //        $url = explode('<a class="figure_detail" href="',$matches1[0],2);
    //        $url = explode('"',$url[1],2);
    //        $tempArr = array_map(function($item){
    //            $temp = explode('"', $item);
    //            $temp1 = explode('.html', $temp[0]);
    //            $temp2 = explode('vid=', $temp1[1]);
    //            return $temp1[0].'/'.$temp2[1].'.html';
    //        },$matches2[0]);
    //        $matches2 = array_merge([$vip_url],$tempArr);
    //    }
    //    elseif ($video_type == 2) {
    //        $url = explode('<a class="figure_detail" href="',$matches1[0],2);
    //        $url = explode('"',$url[1],2);
    //        // $matches2[]= 'https://v.qq.com'.$url[0];
    //        $matches2[]= $vip_url;
    //    }
    }
    elseif ($url_type == 2) {
    //    die($response_data[1]);
        preg_match('/window.playerAnthology=\{[\s\S]*"\}\}\n/',$response_data[1],$matches1);
        $video_json = explode('=',$matches1[0],2);
        $vidie_info = json_decode($video_json[1],true);

        $base_url = 'https://v.youku.com/v_show/id_';
        $vedio_list = array();
        foreach ($vidie_info['list'] as $key => $item) {
    //            $vedio_list_temp['num'] = $item['seq'];
            $vedio_list_temp['num'] = $item['title'];
            $vedio_list_temp['url'] = $base_url . $item['encodevid'] . '.html?&s=' . $item['showId'];
            $vedio_list[] = $vedio_list_temp;
        }
    }
    elseif ($url_type == 3) {
        preg_match('/"albumId":\d+/',$response_data[1],$matches1);
        $albumId = explode(':',$matches1[0],2);

        if ($albumId[1]) {
            $response_data = $this->myHttpClient('http://pcw-api.iqiyi.com/albums/album/avlistinfo?aid='.$albumId[1].'&size=1000&page=1');
            $video_info = json_decode($response_data[1], true);
            foreach ($video_info['data']['epsodelist'] as $key => $item) {
                $vedio_list_temp['url'] = $item['playUrl'];
                $vedio_list_temp['num'] = $item['order'];
                $vedio_list[] = $vedio_list_temp;
            }

    //   if (preg_match('/<a href="\/\/www.iqiyi.com\/[\S]+_[\S]+.html" target="_blank" rseat="\d+_details_album" title="(.*)" class="title-link">(.*)<\/a> /Us',$response_data[1],$matches1)) {
    //        $url = explode('//',$matches1[0],2);
    //        $url = explode('html',$url[1],2);
    //
    //        if ($request_type == 1) {
    //            $response_data = myHttpClient('https://'.$url[0].'html');
    //        }
    //        elseif ($request_type == 2) {
    //            $response_data[1] = file_get_contents('https://'.$url[0].'html');
    //        }
    //
    ////        preg_match_all('/\/\/www.iqiyi.com\/v_[\S]*.html"/',$response_data[1],$matches2);
    //        preg_match_all('/<a href="\/\/www.iqiyi.com\/v_[\S]*.html" rseat="\d+_[\S]+_tuwentitle" data-pb="[\S]+" target="_blank">[\s]+第\d+集[\s]+<\/a>\n/',$response_data[1],$matches2);
    //
    //        foreach ($matches2[0] as $key => $item) {
    //            $temp = explode('"', $item, 3);
    //            $vedio_list_temp['url'] = 'https:'.$temp[1];
    //            $temp = explode('第', $temp[2], 2);
    //            $temp = explode('集', $temp[1], 2);
    //            $vedio_list_temp['num'] = $temp[0];
    //            $vedio_list[] = $vedio_list_temp;
    //        }
        } else {
            $vedio_list_temp['url'] = $vip_url;
            $vedio_list_temp['num'] = 1;
            $vedio_list[] = $vedio_list_temp;
        }
    }
    elseif ($url_type == 4) {
        preg_match('/window\.__INITIAL_STATE__=\{"(.*)"\]\};/Us',$response_data[1],$matches1);
        $video_json = explode('=',$matches1[0],2);
        $vidie_info = json_decode(rtrim($video_json[1], ';'),true);

        $base_url = 'https://www.bilibili.com/bangumi/play/ep';
        if (stripos($vip_url,'video/av') !== false) {
            $base_url = 'https://www.bilibili.com/video/av';
        }

        $vedio_list = array();
        if (isset($vidie_info["epList"])) {
            foreach ($vidie_info["epList"] as $key => $item) {
                $vedio_list_temp['num'] = $item['titleFormat'];
                $vedio_list_temp['url'] = $base_url . $item['id'];
                $vedio_list[] = $vedio_list_temp;
            }
        } else {
            foreach ($vidie_info["related"] as $key => $item) {
                $vedio_list_temp['num'] = $item['title'];
                $vedio_list_temp['url'] = $base_url . $item['aid'];
                $vedio_list[] = $vedio_list_temp;
            }
            $vedio_list = array_merge([['num'=> $vidie_info['videoData']['title'], 'url' => $vip_url]], $vedio_list);
        }

    }
    else {
        return false;
    }

    die(json_encode(array('data' => $vedio_list)));
  }

public function myHttpClient($url, array $options = array()) {
    $curl = curl_init();
    if (count($options) > 0) {
        curl_setopt_array($curl, $options);
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    $url_host = explode('com', $url);
    curl_setopt($curl, CURLOPT_REFERER, $url_host[0].'com/');//构造来路
//        curl_setopt($curl, CURLOPT_POST, true);
//        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(参数数组));
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //不验证证书
    $UserAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36';
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');//这个是解释gzip内容,防止乱码

    $ip_long = array(
        array('607649792', '608174079'), //36.56.0.0-36.63.255.255
        array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
        array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
        array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
        array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
        array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
        array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
        array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
        array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
        array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
    );

    $rand_key = mt_rand(0, 9);
    $ip = long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$ip, 'CLIENT-IP:'.$ip)); //构造IP

    $response = curl_exec($curl);
    curl_close($curl);
    $response_header = '';
    if (! (strpos($response, "\r\n\r\n") === false)) {
        list ($response_header, $response) = explode("\r\n\r\n", $response, 2);
        while (strtolower(trim($response_header)) === 'http/1.1 100 continue') {
            list ($response_header, $response) = explode("\r\n\r\n", $response, 2);
        }
        $response_header = preg_split('/\r\n/', $response_header, null, PREG_SPLIT_NO_EMPTY);
    }
    return [$response_header,$response];
}
 
}