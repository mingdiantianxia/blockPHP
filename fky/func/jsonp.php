<?php 
namespace fky\func;

/**
 * 处理jsonp跨域
 * @param string $jsoncallback 前端js函数名
 */
function jsonp() {
    header('Content-type: appliciation/json');

    //获取回调函数名
    $jsoncallback = isset($_REQUEST['jsoncallback']) ? $_REQUEST['jsoncallback'] : 'jsoncallback';
    $jsoncallback = trim($jsoncallback);
    $jsoncallback = stripslashes($jsoncallback);
    $jsoncallback = htmlspecialchars($jsoncallback);

    $arguments['rows'] = func_get_args();//获取传给函数的参数（数组）

    //json数据
    $json_data = json_encode($arguments);

    //输出jsonp数据
    echo $jsoncallback."(".$json_data.")";
}