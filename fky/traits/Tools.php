<?php
namespace fky\traits;

trait Tools{

//根据经纬度算距离，返回结果单位是公里，先纬度，后经度
	public function GetDistance($lat1, $lng1, $lat2, $lng2)
	{
		$EARTH_RADIUS = 6378.137;

		$radLat1 = self::rad($lat1);
		$radLat2 = self::rad($lat2);
		$a = $radLat1 - $radLat2;
		$b = self::rad($lng1) - self::rad($lng2);
		$s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
		$s = $s * $EARTH_RADIUS;
		$s = round($s * 10000) / 10000;

		return $s;
	}

    private function rad($d)
    {
        return $d * M_PI / 180.0;
    }


	    /**
     * 检查手机号所属运营商
     * @param $phoneArr
     * @return array
     */
    public function checkPhoneBelongToWhichService($phoneArr)
    {
        $ChinaMobileNum = 0;
        $ChinaUnionNum = 0;
        $ChinaTelcomNum = 0;
        $OtherTelphoneNum = 0;
        $isChinaMobile = "/^134[0-8]\d{7}$|^(?:13[5-9]|147|15[0-27-9]|178|18[2-478])\d{8}$/";   // 移动
        $isChinaUnion  = "/^(?:13[0-2]|145|15[56]|176|18[56])\d{8}$/";  //联通
        $isChinaTelcom = "/^(?:133|153|177|18[019])\d{8}$/"; //电信
        $isOtherTelphone = "/^170([059])\d{7}$/";   //其他运营商

        if(empty($phoneArr) || !is_array($phoneArr))
        {
            return array('ChinaMobileNum'=>0, 'ChinaUnionNum'=>0, 'ChinaTelcomNum'=>0, 'OtherTelphoneNum'=>0);
        }

        foreach ($phoneArr as $phone)
        {
            if(preg_match($isChinaMobile, $phone))
            {
                $ChinaMobileNum++;
            }
            else if(preg_match($isChinaUnion, $phone))
            {
                $ChinaUnionNum++;
            }
            else if(preg_match($isChinaTelcom, $phone))
            {
                $ChinaTelcomNum++;
            }
            else
            {
                $OtherTelphoneNum++;
            }
        }

        return array('ChinaMobileNum'=>$ChinaMobileNum, 'ChinaUnionNum'=>$ChinaUnionNum, 'ChinaTelcomNum'=>$ChinaTelcomNum, 'OtherTelphoneNum'=>$OtherTelphoneNum);
    }

    /**
	 * 获取13位毫秒时间戳
	 * @return string
	 */
	public function  getMillisecond() {
		list($t1, $t2) = explode(' ', microtime());
		return sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);;
	}

    /**
     * 获取毫秒的时间戳
     * @return mixed
     */
    public function getMicrotime()
    {
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode(".", $time);
        return $time2[0];
    }

	//判断是否来自微信的访问
	public function IsWechat()
	{
		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
	
			$is_wechat = false;
	
			if (strpos($user_agent, "MicroMessenger") !== false)
			{
				$is_wechat = true;
			}
	
			return $is_wechat;
		}
	
		return false;
	}

	/**
     * 检测当前客户端是否是支付宝
     * @return bool 返回true表示支付宝
     */
    public function isAlipay()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            $is_alipay = false;

            if (strpos($user_agent, "AlipayClient") !== false) {
                $is_alipay = true;
            }

            return $is_alipay;
        }

        return false;
    }

    /**
     * 将二维数组转化为对象数组
     * @param array $arr
     * @return array
     */
    public function arrayToObjects(array $arr)
    {
        $ret = [];
        foreach ($arr as $item) {
            $ret[] = (object) $item;
        }
        return $ret;
    }

    /**
     * 	作用：array转xml
     */
    public function arrayToXml($arr, $root = 'root')
    {
        $xml = "<{$root}>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";

            }
            else if(is_array($val)) {
                $xml .= $this->arrayToXml($val, $key);
            }
            else {
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</{$root}>";
        return $xml;
    }

    /**
     * 	作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     * Rsa加密
     * @param string $publicKey - 公钥路径或者公钥字符串
     * @param string $content   - 待加密内容
     * @return string
     */
    public function rsaEncrypt($publicKey, $content)
    {
        if (empty($publicKey)) {
            echo "<br/>rsa公钥不能为空, key=".$publicKey."<br/>";
            return false;
        }

        if (is_file($publicKey)) {
            //读取公钥文件
            $pubKey = file_get_contents($publicKey);
            //转换为openssl格式密钥
            $res = openssl_get_publickey($pubKey);
        } else {
            //读取字符串
            $res = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($publicKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }

        ($res) or die('RSA公钥错误。请检查公钥文件格式是否正确');
        $crypto = '';
        foreach (str_split($content, 117) as $chunk) {
            if (!openssl_public_encrypt($chunk, $encryptData, $res)) {
                echo "<br/>rsa encrypt error, " . openssl_error_string() . "<br/>";
                return '';
            }
            $crypto .= $encryptData;
        }
        return base64_encode($crypto);
    }

    /**
     * rsa解密
     * @param string $privateKey    - 私钥路径或者私钥字符串
     * @param string $encryptData   - 密文
     * @return bool|string
     */
    public function rsaDecrypt($privateKey, $encryptData)
    {
        if (empty($privateKey)) {
            return false;
        }

        if (is_file($privateKey)) {
            //读取私钥文件
            $priKey = file_get_contents($privateKey);
            //转换为openssl格式密钥
            $res = openssl_get_privatekey($priKey);
        } else {
            //读字符串
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        $crypto = '';
        foreach (str_split(base64_decode($encryptData), 256) as $chunk) {
            if (!openssl_private_decrypt($chunk, $decryptData, $res)) {
                echo "<br/>rsa decrypt error, " . openssl_error_string() . "<br/>";
                return false;
            }
            $crypto .= $decryptData;
        }
        return $crypto;
    }

    /**
     * 生成请求串号
     * @return int
     */
    public function getRequestId()
    {
        $us = strstr(microtime(), ' ', true);
        return intval(strval($us * 1000 * 1000) . rand(100, 999));
    }

}