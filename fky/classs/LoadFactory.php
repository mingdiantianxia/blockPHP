<?php
 namespace fky\classs;

 /**
  * 函数和类，统一加载类
  * Class LoadFactory
  */
 class LoadFactory
 {
     private static $_instance = null;

     //类实例缓存数组
     private static $modules = array();

     //项目根目录
     private static $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

     //根目录命名空间
     private static $baseNamespace = '';

     //根目录下的函数目录
     private static $_funcDirArr = array(
         array(
             'dir' => 'fky' . DIRECTORY_SEPARATOR . 'func',
             'suffix' => '',//后缀
         )
     );

     //根目录下的类目录
     private static $_classDirArr = array(
         array(
             'dir' => 'fky' . DIRECTORY_SEPARATOR . 'classs',
             'suffix' => '',//后缀
         )
     );

     //目录匹配字符串（用于匹配目录，一次性使用）
     private static $_dirMatchedStr = '';

     public function __construct() {

     }

     /**
      * 设置项目根目录
      * @param string $baseDir 项目根目录
      * @param string $baseNamespace 根目录对应命名空间
      * @return bool
      */
     public static function setBaseDir($baseDir = '', $baseNamespace = '')
     {
         if (!empty($baseDir) && !empty($baseNamespace)) {
             self::$baseDir = $baseDir;
             self::$baseNamespace = $baseNamespace;
             return true;
         }
            return false;
     }

     /**
      * 设置需要加载的函数目录
      * @param array $funcDir 根目录下的函数目录
      * @return bool
      */
     public static function setFuncDir(array $funcDir = [])
     {
         if (!empty($funcDir)) {
             $is_find = 0;
             foreach (self::$_funcDirArr as $key => $item) {
                 if ($item['dir'] == $funcDir['dir']) {
                     $is_find = 1;
                     break;
                 }
             }
             if (!$is_find) {
                 self::$_funcDirArr[] = $funcDir;
             }
         }
         return self::$_funcDirArr;
     }

     /**
      * 设置需要加载的类目录
      * @param array $classDir 根目录下的类目录
      * @return bool
      */
     public static function setClassDir(array $classDir = [])
     {
         if (!empty($classDir)) {
             $is_find = 0;
             foreach (self::$_classDirArr as $key => $item) {
                 if ($item['dir'] == $classDir['dir']) {
                     $is_find = 1;
                     break;
                 }
             }
             if (!$is_find) {
                 self::$_classDirArr[] = $classDir;
             }
         }
         return self::$_classDirArr;
     }


     /**
      * 设置目录匹配字符串(选中目录，一次性)
      * @param string $matched_str
      * @return string
      */
     public static function setDirMatchedStr($matched_str = '')
     {
         self::$_dirMatchedStr = $matched_str;
         return true;
     }

     /**
      * [lf 加载函数]
      * @return [type] [description]
      */
     public static function lf()
     {
         $dirMatchedStr = self::$_dirMatchedStr;
         self::$_dirMatchedStr = '';//销毁匹配字符

         $arguments = func_get_args();//获取传给函数的参数（数组）
         $name = array_shift($arguments);//弹出第一个参数，即函数名
         if ($name == '') {
             die('function name is empty!');
         } else {
             $call_exist = stripos($name, 'call:');//如果有call:字样，就直接返回函数名
             if ($call_exist === 0) {
                 $callf = explode(':', $name);
                 $name = $callf[1];
             }

             self::$baseDir = strtr(self::$baseDir, '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
             if(substr(self::$baseDir, -1) != DIRECTORY_SEPARATOR) {
                 self::$baseDir .= DIRECTORY_SEPARATOR;
             }

             $isFind = 0;
             foreach (self::$_funcDirArr as $key => &$funcDir) {
                 $funcDir['dir'] = strtr($funcDir['dir'], '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
                 if (!empty($dirMatchedStr) && stripos($funcDir['dir'], strtr($dirMatchedStr, '/', DIRECTORY_SEPARATOR)) === false) {
                     continue;
                 }

                 if(substr($funcDir['dir'], -1) == DIRECTORY_SEPARATOR) {
                     $funcDir['dir'] = trim($funcDir['dir'], DIRECTORY_SEPARATOR);
                 }

                 $suffix  = isset($funcDir['suffix'])?$funcDir['suffix']:'';
                 $func = self::$baseDir . $funcDir['dir'] . DIRECTORY_SEPARATOR . strtolower($name) . $suffix . '.php';
                 if (!is_file($func)) {
                     continue;
                 }

                 $isFind = 1;
                 require_once $func;
                 $function = self::$baseNamespace . '\\' . $funcDir['dir'] . '\\' . $name . $suffix;
                 $function = strtr($function, DIRECTORY_SEPARATOR, '\\');
                 break;
             }

             if (!$isFind) {
                 die(' function ' . $name . ' Not Found!');
             }

             if ($call_exist === 0) {
                 return $function;
             } else {
                 return call_user_func_array($function, $arguments);//调用函数，并传递参数
             }
         }
     }

     /*
     加载类
     */
     public static function lc()
     {
         $dirMatchedStr = self::$_dirMatchedStr;
         self::$_dirMatchedStr = '';//销毁匹配字符

         $arguments = func_get_args();//获取传给函数的参数（数组）
         $name = array_shift($arguments);//弹出第一个参数，即类名
         if ($name == '') {
             die('class name is empty!');
         }

         $is_namespace = false;//是否直接根据类名加载

         $name = strtolower($name);
         //有命名空间的类名，直接根据命名空间加载
         if (stripos($name, '\\') !== false) {
             if(substr($name, 0, 1) == '\\') {
                 $name = ltrim($name, '\\');
             }
             $dirMatchedStr = '';
             $is_namespace = true;
         }

         $nameSuff = !empty($dirMatchedStr) ? '-' . $dirMatchedStr : '';
         if (isset(self::$modules[$name . $nameSuff])) {
             return self::$modules[$name . $nameSuff];
         }

         self::$baseDir = strtr(self::$baseDir, '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
         if(substr(self::$baseDir, -1) != DIRECTORY_SEPARATOR) {
             self::$baseDir .= DIRECTORY_SEPARATOR;
         }

         if ($is_namespace) {
             $class_name = '\\' . $name;
             $class = self::$baseDir . $name . '.php';
             $class = strtr($class, '\\', DIRECTORY_SEPARATOR); // 文件标准路径
             if (!is_file($class)) {
                 die(' class ' . $class_name . ' Not Found!');
             }
             require_once $class;

         } else {
             $isFind = 0;
             foreach (self::$_classDirArr as $key => &$classDir) {
                 $classDir['dir'] = strtr($classDir['dir'], '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
                 if (!empty($dirMatchedStr) && stripos($classDir['dir'], strtr($dirMatchedStr, '/', DIRECTORY_SEPARATOR)) === false) {
                     continue;
                 }

                 if(substr($classDir['dir'], -1) == DIRECTORY_SEPARATOR) {
                     $classDir['dir'] = trim($classDir['dir'], DIRECTORY_SEPARATOR);
                 }
                 $suffix  = isset($classDir['suffix'])?$classDir['suffix']:'';
                 $class = self::$baseDir . $classDir['dir'] . DIRECTORY_SEPARATOR . $name . $suffix . '.php';
                 if (!is_file($class)) {
                     continue;
                 }

                 $isFind = 1;
                 require_once $class;
                 $class_name = self::$baseNamespace . '\\' .$classDir['dir'] . '\\' . ucfirst($name).$suffix;
                 $class_name = strtr($class_name, DIRECTORY_SEPARATOR, '\\');
                 break;
             }

             if (!$isFind) {
                 die(' class ' . $name . ' Not Found!');
             }
         }


         $class_name = new \ReflectionClass($class_name);//反射类
         self::$modules[$name . $nameSuff] = $class_name->newInstanceArgs($arguments);//传入参数
         return self::$modules[$name . $nameSuff];
     }


     /**
      * 当调用了没有的方法时，自动调用这个方法（必须先new）
      * [__call 加载函数]
      * @param  [type] $name      [函数名]
      * @param  [type] $arguments [参数数组]
      * @return [type]            [description]
      */
     public function __call($name, $arguments)
     {
         $dirMatchedStr = self::$_dirMatchedStr;
         self::$_dirMatchedStr = '';//销毁匹配字符

         self::$baseDir = strtr(self::$baseDir, '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
         if(substr(self::$baseDir, -1) != DIRECTORY_SEPARATOR) {
             self::$baseDir .= DIRECTORY_SEPARATOR;
         }

         $isFind = 0;
         foreach (self::$_funcDirArr as $key => &$funcDir) {
             $funcDir['dir'] = strtr($funcDir['dir'], '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
             if (!empty($dirMatchedStr) && stripos($funcDir['dir'], strtr($dirMatchedStr, '/', DIRECTORY_SEPARATOR)) === false) {
                 continue;
             }

             if(substr($funcDir['dir'], -1) == DIRECTORY_SEPARATOR) {
                 $funcDir['dir'] = trim($funcDir['dir'], DIRECTORY_SEPARATOR);
             }

             $suffix  = isset($funcDir['suffix'])?$funcDir['suffix']:'';
             $func = self::$baseDir . $funcDir['dir'] . DIRECTORY_SEPARATOR . strtolower($name) . $suffix . '.php';
             if (!is_file($func)) {
                 continue;
             }

             $isFind = 1;
             require_once $func;
             $function = self::$baseNamespace . '\\' . $funcDir['dir'] . '\\' . $name . $suffix;
             $function = strtr($function, DIRECTORY_SEPARATOR, '\\');
             break;
         }

         if (!$isFind) {
             die(' function ' . $name . ' Not Found!');
         }

         if (count($arguments) > 0) {
             return call_user_func_array($function, $arguments);//调用函数，并传递参数
         } else {
             return $function;//如果没有参数，就直接返回函数名
         }

     }


     /**
      * 当调用的静态方法不存在时，会自动调用__callStatic方法
      * [__callStatic 加载类]
      * @param  [type] $name      [调用的类名]
      * @param  [type] $arguments [参数数组]
      * @return [type]            [description]
      */
     public static function __callStatic($name, $arguments)
     {
         $dirMatchedStr = self::$_dirMatchedStr;
         self::$_dirMatchedStr = '';//销毁匹配字符

         //调用的类名，参数数组
         $name = strtolower($name);
         $nameSuff = !empty($dirMatchedStr) ? '-' . $dirMatchedStr : '';
         if (isset(self::$modules[$name . $nameSuff])) {
             return self::$modules[$name . $nameSuff];
         }

         self::$baseDir = strtr(self::$baseDir, '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
         if(substr(self::$baseDir, -1) != DIRECTORY_SEPARATOR) {
             self::$baseDir .= DIRECTORY_SEPARATOR;
         }

         $isFind = 0;
         foreach (self::$_classDirArr as $key => &$classDir) {
             $classDir['dir'] = strtr($classDir['dir'], '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
             if (!empty($dirMatchedStr) && stripos($classDir['dir'], strtr($dirMatchedStr, '/', DIRECTORY_SEPARATOR)) === false) {
                 continue;
             }

             if(substr($classDir['dir'], -1) == DIRECTORY_SEPARATOR) {
                 $classDir['dir'] = trim($classDir['dir'], DIRECTORY_SEPARATOR);
             }

             $suffix  = isset($classDir['suffix'])?$classDir['suffix']:'';
             $class = self::$baseDir . $classDir['dir'] . DIRECTORY_SEPARATOR . $name . $suffix . '.php';
             if (!is_file($class)) {
                 continue;
             }

             $isFind = 1;
             require_once $class;
             $class_name = self::$baseNamespace . '\\' .$classDir['dir'] . '\\' . ucfirst($name).$suffix;
             $class_name = strtr($class_name, DIRECTORY_SEPARATOR, '\\');
             break;
         }

         if (!$isFind) {
             die(' class ' . $name . ' Not Found!');
         }

         $class_name = new \ReflectionClass($class_name);//反射类
         self::$modules[$name.$nameSuff] = $class_name->newInstanceArgs($arguments);//传入参数
         return self::$modules[$name.$nameSuff];
     }

 }

