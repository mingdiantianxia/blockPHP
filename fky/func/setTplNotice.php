<?php
namespace fky\func;
require __DIR__.'/../class/ihttp.class.php';
//设置微信公众号模版消息
/*  IT科技	互联网/电子商务	1
	IT科技	IT软件与服务	2
*/
/*
$params['token']	string 
$params['set_industry'] bool 设置行业
$params['add_template'] bool 添加模版
 */
function setTplNotice($params = array())
{	
	 if (isset($params['token'])) {
	 	$token = $params['token'];
	 }
	 if (isset($params['set_industry']) && $params['set_industry'] == false) {
	 	$set_industry = false;
	 } else {
	 	$set_industry = true;
	 }
	 if (isset($params['add_template']) && $params['add_template'] == false) {
	 	$add_template = false;
	 } else {
	 	$add_template = true;
	 }	 
	if (empty($token)) {
		die('缺少公众号token参数');
	}
	$ihttp = new \fky\classs\Ihttp;
	if ($set_industry) {
		$url = "https://api.weixin.qq.com/cgi-bin/template/api_set_industry?access_token=".$token;
		$todata = array(
			"industry_id1"=>"1",
			"industry_id2"=>"2"
			);
		$todata = json_encode($todata);
		$data = $ihttp->post($url, $todata);
	}

	if ($add_template) {
		//模版编号
		$tpl = array(
			'OPENTM205213550',//订单生成通知
			'OPENTM200746866',//订单提交成功通知
			'OPENTM201594720',//自提订单提交成功通知
			'TM00850',//订单取消通知
			'OPENTM204987032',//订单支付成功通知
			'OPENTM202243318',//订单发货通知
			'OPENTM202314085',//订单确认收货通知
			'TM00431',//退款申请通知
			'TM00430',//退款成功通知
			'TM00432',//退款申请驳回通知
			'OPENTM200605630',//会员升级通知(任务处理通知)
			'TM00977',//充值成功通知
			'TM00004',//充值退款通知
			'TM00979',//提现提交通知
			'TM00980',//提现成功通知
			'TM00981'//提现失败通知
			);
		foreach ($tpl as $tplk) {
			$url = "https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token=".$token;
			$todata = array(
			"template_id_short"=>$tplk,
				);
			$todata = json_encode($todata);
			$data = $ihttp->post($url, $todata);
			// $result = json_decode($data,true);
		}
	}

	$url="https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=".$token;
	$data = $ihttp->get($url);
	$result = json_decode($data,true);
	return $result;

}
?>