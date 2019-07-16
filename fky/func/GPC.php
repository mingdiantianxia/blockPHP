<?php
namespace fky\func;
require_once __DIR__.DIRECTORY_SEPARATOR.'XssFilter.php';
/**
 * 获取post和get的参数
 * @author fukaiyao
 * @param $param      要获取的参数名
 * @param $default    单项默认值
 * @param $filter     是否xss过滤
 * @return int|string|array|bool
 */
function GPC($param = '', $default = '', $filter = true){
    $_GPC = false;
    if (empty($param)) {
        $_GPC = $_GET;
        if (!empty($_POST)) {
            foreach ($_POST as $item => $key) {
                $_GPC[$item] = $key;
            }
        }
    }
    elseif (isset($_GET[$param]) || isset($_POST[$param])) {
        if (isset($_GET[$param])) {
            $_GPC = $_GET[$param];
        }
        if (isset($_POST[$param])) {
            $_GPC = $_POST[$param];
        }
    }
    else {
        return $default;
    }

    if ($filter) {
        return \fky\func\XssFilter($_GPC);
    } else {
        return $_GPC;
    }

}

/**
 * 获取post参数
 * @author fukaiyao
 * @param $param      要获取的参数名
 * @param $default    单项默认值
 * @param $filter     是否xss过滤
 * @return int|string|array|bool
 */
function getPost($param = '', $default = '', $filter = true){
    $temp = false;
    if (empty($param)) {
        $temp = $_POST;
    }
    elseif (isset($_POST[$param])) {
        $temp = $_POST[$param];
    }
    else {
        return $default;
    }

    if ($filter) {
        return \fky\func\XssFilter($temp);
    } else {
        return $temp;
    }

}

/**
 * 获取get参数
 * @author fukaiyao
 * @param $param      要获取的参数名
 * @param $default    单项默认值
 * @param $filter     是否xss过滤
 * @return int|string|array|bool
 */
function getQuery($param = '', $default = '', $filter = true){
    $temp = false;
    if (empty($param)) {
        $temp = $_GET;
    }
    elseif (isset($_POST[$param])) {
        $temp = $_GET[$param];
    }
    else {
        return $default;
    }

    if ($filter) {
        return \fky\func\XssFilter($temp);
    } else {
        return $temp;
    }

}