<?php
namespace fky\classs;
class Api{
	const JSON="json";
	/**
	 * [show description]
	 * @param  integer $code   [状态码]
	 * @param  string $message [提示信息]
	 * @param  array  $data    [数据]
	 * @param  string $type    [数据类型]
	 * @return string         
	 */
	public static function show($code='',$message='',$data=array(),$type=self::JSON){
		if (!is_numeric($code)) {
				return;
		}

		$type=isset($_GET['format'])?$_GET['format']:$type;

		if ($type=='json') {
			self::json($code,$message,$data);
			exit;
		}elseif ($type=='array') {
			$result=array(
				'code'=>$code,
				'message'=>$message,
				'data'=>$data
			);
			var_dump($result);
			die;
		}elseif ($type=='xml') {
			self::xmlEncode($code,$message,$data);
			exit;
		}else{
			//其他方法
		}

	}


	 /**
	  * [json description]
	 * @param  integer $code   [状态码]
	 * @param  string $message [提示信息]
	 * @param  array  $data    [数据]
	 * @return string    
	  */
	 public static function  json($code,$message='',$data=array()){
	 	if (!is_numeric($code)) {
				return ''; 		
	 	}
	 	$result=array(
	 			'code'=>$code,
	 			'message'=>$message,
	 			'data'=>$data
	 		);
	 	// echo urldecode(json_encode(urlencode($result)));
	 	echo json_encode($result);
	 	exit;

	 }

	/**
	 * [xmlEncode description]
	 * @param  integer $code   [状态码]
	 * @param  string $message [提示信息]
	 * @param  array  $data    [数据]
	 * @return string         
	 */
	public static function xmlEncode($code,$message='',$data=array()){
		if (!is_numeric($code)) {
				return '';
		}
		$result=array(
				'code'=>$code,
				'message'=>$message,
				'data'=>$data

			);
		header('Content-Type:text/xml');
		$xml.="<?xml version='1.0' encoding='UTF-8'?>\n";
		$xml.="<root>\n";
		$xml.=self::xmlToEncode($result);
		$xml.="</root>";
		echo $xml;
	}
	/**
	 * [xmlToEncode description]
	 * @param  array $data [数据]
	 * @return string       
	 */
	public static function xmlToEncode($data){
		$xml=$attr="";
		foreach ($data as $key => $value) {
			if (is_numeric($key)) {
				$attr=" id='{$key}'";
				$key="item";
			}
			$xml.="<{$key}{$attr}>";
			$xml.=is_array($value)?self::xmlToEncode($value):$value;
			$xml.="</{$key}>\n";
		}
		return $xml;
	}
}

