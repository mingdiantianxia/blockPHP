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


}