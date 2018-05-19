<?php
namespace fky\func;
require_once __DIR__.DIRECTORY_SEPARATOR.'XssFilter.php';
/**
 * 获取post和get的参数
 * @author fukaiyao
 * @param $param      要获取的参数名
 * @param $filter     是否xss过滤
 * @return int|string|array|bool
 */
function GPC($param = '', $filter = true){
    $_GPC = false;
    if (empty($param)) {
        $_GPC = $_GET;
        if (!empty($_POST)) {
            foreach ($_POST as $item => $key) {
                $_GPC[$item] = $key;
            }
        }
    }
    elseif (isset($_GET[$param])) {
        $_GPC = $_GET[$param];
    }
    elseif (isset($_POST[$param])) {
        $_GPC = $_POST[$param];
    }

    if ($filter) {
        return \fky\func\XssFilter($_GPC);
    } else {
        return $_GPC;
    }

}