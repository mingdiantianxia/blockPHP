<?php 
 namespace fky\classs;

 /**
  * 函数和类，统一加载类
  * Class LoadFactory
  */
 class LoadFactory
 {
     private static $_instance = null;

     private static $modules = array();

     //项目根目录
     private static $baseDir = __DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;

     //根目录命名空间
     private static $baseNamespace = 'fky';

     private static $FuncDir = 'func';

     private static $ClassDir = 'classs';

     public function __construct() {

     }

     public static function setBaseDir($baseDir = '', $baseNamespace = '')
     {
         if (!empty($baseDir) && !empty($baseNamespace)) {
             self::$baseDir = $baseDir;
             self::$baseNamespace = $baseNamespace;
             return true;
         }
            return false;
     }

     public static function setFuncDir($FuncDir = '')
     {
         if (!empty($FuncDir)) {
             self::$FuncDir = $FuncDir;
             return true;
         }
         return false;
     }

     public static function setClassDir($ClassDir = '')
     {
         if (!empty($ClassDir)) {
             self::$ClassDir = $ClassDir;
             return true;
         }
         return false;
     }

     /**
      * [lf 加载函数]
      * @return [type] [description]
      */
     public static function lf()
     {
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
             $func = self::$baseDir . DIRECTORY_SEPARATOR . self::$FuncDir . DIRECTORY_SEPARATOR . strtolower($name) . '.php';

             if (!is_file($func)) {
                 die(' function ' . $name . ' Not Found!');
             }
             require_once $func;
             $function = self::$baseNamespace . '\\' . self::$FuncDir . '\\' . $name;

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
         $arguments = func_get_args();//获取传给函数的参数（数组）
         $name = array_shift($arguments);//弹出第一个参数，即类名
         if ($name == '') {
             die('class name is empty!');
         }

         $name = strtolower($name);
         if (isset(self::$modules[$name])) {
             return self::$modules[$name];
         }
         $class = self::$baseDir . DIRECTORY_SEPARATOR . self::$ClassDir . DIRECTORY_SEPARATOR . $name . '.php';

         if (!is_file($class)) {
             die(' class ' . $name . ' Not Found!');
         }
         require_once $class;
         $class_name = self::$baseNamespace . '\\' . self::$ClassDir . '\\' . ucfirst($name);

         $class_name = new \ReflectionClass($class_name);//反射类
         self::$modules[$name] = $class_name->newInstanceArgs($arguments);//传入参数
         return self::$modules[$name];
     }


     /**
      * 当调用了没有的方法时，自动调用这个方法
      * [__call 加载函数]
      * @param  [type] $name      [函数名]
      * @param  [type] $arguments [参数数组]
      * @return [type]            [description]
      */
     public function __call($name, $arguments)
     {//调用的函数名，参数数组
         $func = self::$baseDir . DIRECTORY_SEPARATOR . self::$FuncDir . DIRECTORY_SEPARATOR . strtolower($name) . '.php';

         if (!is_file($func)) {
             die(' function ' . $name . ' Not Found!');
         }
         require_once $func;
         $function = self::$baseNamespace . '\\' . self::$FuncDir . '\\' . $name;

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
         //调用的类名，参数数组
         $name = strtolower($name);
         if (isset(self::$modules[$name])) {
             return self::$modules[$name];
         }
         $class = self::$baseDir . DIRECTORY_SEPARATOR . self::$ClassDir . DIRECTORY_SEPARATOR . $name . '.php';

         if (!is_file($class)) {
             die(' class ' . $name . ' Not Found!');
         }
         require_once $class;
         $class_name = self::$baseNamespace . '\\' . self::$ClassDir . '\\' . ucfirst($name);

         $class_name = new \ReflectionClass($class_name);//反射类
         self::$modules[$name] = $class_name->newInstanceArgs($arguments);//传入参数
         return self::$modules[$name];
     }

 }

