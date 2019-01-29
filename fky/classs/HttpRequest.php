<?php
namespace fky\classs;

class HttpRequest {
	//POST请求
	//参数1是请求的url
	//参数2是发送的数据的数组
	//参数3是其他POST选项
	public static function POST($url, $post = array(), array $options = array())
	{
		$defaults = array(
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 0,
				CURLOPT_FRESH_CONNECT => 1,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_TIMEOUT => 15,
                CURLOPT_VERBOSE => 1,
				CURLOPT_SSLVERSION => 1,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_SSL_VERIFYHOST => 2
		);
        $options = $options + $defaults;

        $httpClient = new HttpClient();
        foreach ($options as $key=>$value)
        {
            if($value)
            {
                $httpClient->setOpt($key, $value);
            }
        }

        $httpClient->post($url, $post);
        return HttpRequest::response($httpClient);
	}
	
	//GET请求
	//参数1是请求的url
	//参数2是发送的数据的数组
	//参数3是其他GET选项
	public static function GET($url, $get = array(), array $options = array())
	{
		$defaults = array(
				CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get),
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_TIMEOUT => 15,
				CURLOPT_SSLVERSION => 1,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_SSL_VERIFYHOST => 2
		);
		$options = $options + $defaults;

        $httpClient = new HttpClient();
        foreach ($options as $key=>$value)
        {
            if($value)
            {
                $httpClient->setOpt($key, $value);
            }
        }

        $httpClient->get($url, $get);
        return HttpRequest::response($httpClient);
	}
	
	//异步POST
	public static function POST_ASYNC($url, $post = array(), array $options = array())
	{
        $httpClient = new HttpClient();
        if(count($options) > 0)
        {
            foreach ($options as $key=>$value)
            {
                if($value)
                {
                    $httpClient->setOpt($key, $value);
                }
            }
        }

        $httpClient->post($url, $post);
        return HttpRequest::response($httpClient);
	}
	
	//异步GET
	public static function GET_ASYNC($url, array $get = array(), array $options = array())
	{
        $httpClient = new HttpClient();
        if(count($options) > 0)
        {
            foreach ($options as $key=>$value)
            {
                if($value)
                {
                    $httpClient->setOpt($key, $value);
                }
            }
        }

        $httpClient->get($url, $get);
        return HttpRequest::response($httpClient);
	}

    /**
     * 需要证书的post请求封装
     * @param string $api        -请求url
     * @param mixed $params      -请求参数,数组,对象,字符串xml
     * @param mixed $sslCert     -证书路径
     * @param mixed $sslKey      -秘钥路径
     * @return string | false    - 成功返回请求结果
     * @throws \Exception
     */
    public function POSTSSL($api,$params,$sslCert,$sslKey)
    {
        $httpClient = new HttpClient();
        $httpClient->setOpt(CURLOPT_TIMEOUT, 10);
        $httpClient->setOpt(CURLOPT_SSL_VERIFYPEER,false);
        $httpClient->setOpt(CURLOPT_SSL_VERIFYHOST,false);
        $httpClient->setOpt(CURLOPT_SSLCERT,$sslCert);
        $httpClient->setOpt(CURLOPT_SSLKEY,$sslKey);
        $response = $httpClient->post($api, $params);
        if (!$response->isSuccess()) {
            if ($response->curl_error_code == CURLE_OPERATION_TIMEOUTED) { //timeout
                throw new \Exception("api={$api}");
            }
            return false;
        }
        return $response->response;
    }
	
	public static function POSTJSON($url, $json)
	{
		return self::POST($url, $json);
	}

    /**
     * 相应请求结果
     * @param \fky\classs\HttpClient $client
     * @return bool
     */
	public static function response(HttpClient $client)
    {
        if($client && is_object($client))
        {
            if($client->isSuccess())
            {
                return $client->response;
            }
            if($client->isError())
            {
                return $client->error_message;
            }
        }

        return false;
    }

}