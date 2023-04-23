<?php
namespace controllers\test;
use controllers\console\Test2Controller;
use fky\classs\CoHttpClient;
use fky\traits\BaseTool;
use fky\traits\Tools;
use sskaje\mqtt\Exception;
use Swoole\Coroutine as Co;

/**
* \HomeController
*/
class TestController extends \controllers\BaseController
{
	use BaseTool;
	use Tools;

  public function test()
  {
      var_dump(posix_getpid());

      go(function () {
          try{
              exit(0);
          }catch (\Exception $e) {
              var_dump($e->getMessage());

          }
          Co::defer(function () {
              echo "回收协程资源, cid=".Co::getCid();
          });

      });

      go(function () {
          var_dump('good');

      });

      die;
      $http = CoHttpClient::getInstance();
     $http->get($this->GPC('url'));
     die;

      $data = $this->rsaEncrypt(
          'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqWmpFXJ8LFRWN9OitbH2fyafESkuQWDDbDjyV1RQhaapOb5Ny0OEEVQXyuFeC6l6+m0VU71y2xjsUTAvZhz7UAk6N5cwbRVY4Wc7cLmmVhjkF71r+mIBbDrkdb39QXFP2VQvz+Iddpj+JlhbUvmyIbtSgGo4loNaxeQoSNVHiopnFfMJC220yyhzetvLg2X430sjXny3ue94neB4NwkGf8DsHn6Gm+nhNqRyOjn2owqv7BlphPeKUy36xpoaAFV3xZY6B1feaTrx3deZX3uU+SSTAa6Rc5pj7q3TdD30z7+W7/reuaKOP+iGSZNI1wKHUum+iNatPKESFCT0Gmk4pQIDAQAB',
          '附近的快乐十分简单快乐十分简单快乐司法局'
      );
      $data = $this->rsaDecrypt('MIIEpAIBAAKCAQEAqWmpFXJ8LFRWN9OitbH2fyafESkuQWDDbDjyV1RQhaapOb5Ny0OEEVQXyuFeC6l6+m0VU71y2xjsUTAvZhz7UAk6N5cwbRVY4Wc7cLmmVhjkF71r+mIBbDrkdb39QXFP2VQvz+Iddpj+JlhbUvmyIbtSgGo4loNaxeQoSNVHiopnFfMJC220yyhzetvLg2X430sjXny3ue94neB4NwkGf8DsHn6Gm+nhNqRyOjn2owqv7BlphPeKUy36xpoaAFV3xZY6B1feaTrx3deZX3uU+SSTAa6Rc5pj7q3TdD30z7+W7/reuaKOP+iGSZNI1wKHUum+iNatPKESFCT0Gmk4pQIDAQABAoIBAEWPQcKxoDSfaEtB1XQfHyP0Gqn0K67iaTsdYrvivbEyzhcMgWqtTSPEUISX5oKJUxpSAcjBZ9B4OkfXrg6SZcnmEAZVSKfxdO4P8gMF5ztAux7YQuaqqQTkZXvGx57ARNXqUDteD1Tr2qap7s1yAucAwA5EDvoV8wZ/+N523AoQv8mynQYWY3twMFjlKulaqOi3A7ElsADH4U+1424QCt0vtulHrccqC9pg1cbsS887eUO8FawgVLXWh9f1bebzB7LnUG20uB48R0QdEmRv7mE8jXxS46jgJzcWeEoXhdfXnGAUgpWxRjfOCmJ4JfdAnK13GalfBEhGj/NQwW5dQ0ECgYEA6R4JqM75JRzZp3cwEVG2+DzoMmjDMILle6tyvlSx/2VCQBz6eI10O7+LuC2oi8o0mzFTtVgDJYHoR2aOSN8KZs588WKhohsRBD6E8q35EUXCFmLpoCwrAG7hiWlkMD5FhCk//oyFm8o5EHGuGYrHl3hjuPq2Cvqx9g4MF5FXAdECgYEAugrNPM0RGeoTNMRmnP32aJ0iv95RWnpUVecMa5Qf5b8wFF6cGscP46kme1ts1LCQDnopl5hLxIz45C89M/tQ2CaluDVkd+ObuXciaaPfs41g9S/AjpGYUKj8MiOWcOv0s2zKPkXUorAYmiu0W2r0FxijuzY8IZpkd2cP3TNDCpUCgYEA45qbTcE/Clg/vj0lplNFNNuqzcTxhoTW8Ec2EdT5sWU5KQXiGx/pM4jSLvINVOcJM9kWZMFY2R8cHdJo64cxTa0f2kI1k+OfWqh7/8GSo6WbWWYbunJFTff0pshKtLun/eCUhcDHlpL74i1MEc4pD5/QpcPLR677YETY043pCHECgYEAoT7vd709Dzrj/p4jWfp78VwQXD/yPvs70WB6UVuG8fftUhpWLpdN3EIlSlGJWCbYFNQo7G1hbi/JIO0YnM872LxWcfxE4exyciMhvnH8V4E4AgqrWGY0n+R3AXX61FCOPF0URTj8/SynhihPH9TpToNalc6B+5X3cc3v4AaoGqkCgYBRH6ITaMojgz3gpWYXXDzyJkfJN+iySC8+YZ0JVkrYloFWdDP3gzfInnRiOXOAuQi8o+jJfAEsxaKiXwFn3DnVPaI2KmWprU7MwssykS2FBFLbMeZ/zHfZ7oJItVhYifYKmbYW2SSQmabFeH1hTiP8vXTm91ECx8iIHorgTcIWow==',$data);
      var_dump($data);
      var_dump($this->GPC());
      die;

  	// $test2 = new Test2Controller();
  	// $test2->test();
  	loadc('log')->info('good',['good1','good2']);

  	  $data = loadc('db')->pdo->query('show databases', \PDO::FETCH_ASSOC)->fetchAll();
  	  var_dump($data);
  	  die('good2');
  	 // $this->showResponse(200,'',$data,'arr');
      //  	 $response = loadc('HttpRequest')->GET('https://hao.360.cn/?360safe');

      echo loadc('template')->make('admin/hello', ['a' => 'blockPHP测试页', 'time' => time()])->render();
  }

  public function test2()
  {
     $rev = $this->GPC();
     var_dump($rev);
//      sleep(2);
//      $arguments = array(
//          'class_name' => 'test',
//          'func_name' => 'test'
//      );
//      $paramStr = json_encode($arguments);
//      $data = $this->rsaEncrypt(
//          'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuToj3eZTKN4lA1e0QjzYEubEW/wm7PcpacszLNYZoiILNfq3T8+GbAq36slE2klyVZkO+ZLFu8sWdhENnNT0e25bHMexvAwKqD8DvnNz7QWNvPAWoeTQzX7f0LLjX6l5PZhocr1SDPFjJba1YJJeWEOHdisVmrvlZPzJSOXfRmRF4fGfX88LD4g+g/URB3lcGXPvB0KZcAZQ2KfknCWf5CvFWsSa+aQVyrEbxc0dU+YkL+4p3SSD4rtk6GljtUmqtRHbX7K1rMlM3pej5Ge6ghHtWcXcW9sKXcyCO6LA8X8Hiz4BNF4+oQseAOUewLx8e4F1MvJDtCIuRwNTG9UPQQIDAQAB',
//          $paramStr
//      );

      $data = $this->rsaDecrypt('MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC5OiPd5lMo3iUDV7RCPNgS5sRb/Cbs9ylpyzMs1hmiIgs1+rdPz4ZsCrfqyUTaSXJVmQ75ksW7yxZ2EQ2c1PR7blscx7G8DAqoPwO+c3PtBY288Bah5NDNft/QsuNfqXk9mGhyvVIM8WMltrVgkl5YQ4d2KxWau+Vk/MlI5d9GZEXh8Z9fzwsPiD6D9REHeVwZc+8HQplwBlDYp+ScJZ/kK8VaxJr5pBXKsRvFzR1T5iQv7indJIPiu2ToaWO1Saq1EdtfsrWsyUzel6PkZ7qCEe1Zxdxb2wpdzII7osDxfweLPgE0Xj6hCx4A5R7AvHx7gXUy8kO0Ii5HA1Mb1Q9BAgMBAAECggEBAJQQacNj7m3oxeWaIjogsQjK94QgWG1sVep/yHS64Nq+JAAOXqKp3WZYWxSPtz5XH9JU+6d9MBsiCN5wW1VW1eglGygyaV1MsugzFycS20RJADhcI3tekZrzJK6LkT5TFtuqDTRLKl+2Re9L0klUN56OvKY1YpvCP2kuuShQuN5mxWAyzaNSiKuDNDT+bV9qdgvRxSQw7GcmC47aaOr+aR68WNTFRga9iQB+oiOZONrvxjtg7Z39qCiBIeAWAd8WNfNCEl6oVvsReN5jkt2VRdzNZ1NgZIa4p8zTVT+j6kVh3FIMWTpEHMAZGFnCmmk3lRAQV1mxzWbqld8VuFOlw9ECgYEA2zUWkFGUUh3vyzJm1qoDzinEYbgvXeUssol225dI7ygJaDY0lj1Hf2/6jR1S1BTr7isw5qPQsXD1xTbXISnEemrPJB0PuhFwgCXQ/Ohl7urolEvrMii3QM5a006pdv7PIPtbjd4MNB+xfe1cU93hNeX38E5CL03y8vIDz5JJ46MCgYEA2FD8iG3AiZybrU/cmQUDJFDLIJn3kkoKpbGKKw/juf6Lwq5k2KOPMcIineS5FjCbj7OqKSx9K/7pIMrEbReg/4i2ITfVcZyYe3zEJOqmKgdhnxrVmlAVE3pRju7iNvZIc9MkA027p8ZrE/4O0l+SoUMLVHx/4e07JrjN6GPtD8sCgYEA1yS5qpeG3TmommQzbsiax4NSzR36z0sYnXoxf8Bxwtgms3NQFYy7WaZL4KhBHQoTrUQS0KtDLoRRk2gJqPDXd9bQyv8C6nonUn7LKQ3mzaEc+D+y7R3tDdv6ZJ410SaxoAtThl/C1n2stI18KowAd+fneqE0cD0vD6bceqN3hcUCgYB88nKb30FKe4JUnn+eh42kCWL7RtQA1PHHeYCElR4GDijuX8tycy9AH5HdQANE9Pi2DaIpPEBlDE3emiDRnsdpMq95CoYopLpTAeNOK5elSTQzMc3V35H8+Tdo50UVWDbLaFcx7VGKfIrXNnWbQIcyzMYbm/pGJsB3AmI1bJq+9wKBgDw5yl0ieum8+eQAeLNRSMPM7v6VzPzrl1Wxfp4+Qa+bN7lrE+IGHs1lX9Hz/8dbVVK1mVwPR/W5nTjkVbuShvbZTwP9I+dxGgb3UiJJmmooK6adWRSJVIpgKCU1GucWGxIASqZeW9feGzBkLBqB80qPDtuYmwhb5hJTVPKwJmyf',$rev['data']);
      var_dump($data);
      var_dump(json_decode($data, true));
//      var_dump($this->GPC());
      loadc('log')->info('good', $rev);
  }
}