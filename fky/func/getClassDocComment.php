<?php 
namespace fky\func;

/**
 * 获取类的注释
 * @param string|object $class 类名|实例化对象
 * @param string $func  类的方法
 * @param string $field 要查询的注释字段
 * @return bool|string
 * @throws \ReflectionException
 */
function getClassDocComment($class, $func = '', $field = '') {
    if (is_string($class) && !class_exists($class)) {
        return false;
    }

    try{
        $classInfo = new \ReflectionClass($class);//反射类

        if (!empty($func)) {
            if (!$classInfo->hasMethod($func)) { //方法不存在
                return false;
            }
            $docComment = $classInfo->getMethod($func)->getDocComment();
        } else {
            $docComment = $classInfo->getDocComment();
        }

        if (!$docComment) {
            return false;
        }

        if (!empty($field)) {
            $result = null;
            $res = preg_match_all('/@\s*'.$field.'\s+([^\*]*)/', $docComment,$result);
            if (!$res) {
                return false;
            }
            if (!isset($result[1])) {
                return false;
            }

            $res = [];
            $res[$field] = $result[1][0];

        } else {
            $p = '/@\s*([a-zA-Z_-]+)\s+([^\*]*)/';
            $result = null;
            $res = preg_match_all($p, $docComment,$result);
            if (!$res) {
                return false;
            }
            if (!isset($result[1]) || !isset($result[2])) {
                return false;
            }
            $res = [];
            foreach ($result[1] as $k => $key) {
                $key = trim($key);
                $res[$key] = $result[2][$k];
            }
        }
    }catch (\Exception $e) {
        return false;
    }

    return $res;
}
