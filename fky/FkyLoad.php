<?php 
// namespace fky;

   /**
    * [loadf 加载函数]
    * @return [type] [description]
    */
    function loadf() {
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
            $func =  dirname(__FILE__).DIRECTORY_SEPARATOR.'func'.DIRECTORY_SEPARATOR. $name . '.php';

            if (!is_file($func)) {
                die(' function ' . $name . ' Not Found!');
            }
            require_once $func;
            $function = 'fky\\func\\'.$name;

            if ($call_exist === 0) {
                return $function;
            } else {
                return  call_user_func_array ($function , $arguments);//调用函数，并传递参数
            }
        }
    }
    /*
    加载类
    */
    function loadc() {
        $arguments = func_get_args();//获取传给函数的参数（数组）
        $name = array_shift($arguments);//弹出第一个参数，即类名
        if ($name == '') {
            die('class name is empty!');
        }

        $name = strtolower($name);
        static $fky_modules = array();
        if (isset($fky_modules[$name])) {
            return $fky_modules[$name];
        }
        $class =  dirname(__FILE__).DIRECTORY_SEPARATOR.'classs'.DIRECTORY_SEPARATOR. $name . '.php';

        if (!is_file($class)) {
            die(' class ' . $name . ' Not Found!');
        }
        require_once $class;
        $class_name = 'fky\\classs\\' . ucfirst($name);

        $class_name = new \ReflectionClass($class_name);//反射类
        $fky_modules[$name] = $class_name->newInstanceArgs($arguments);//传入参数
        return $fky_modules[$name];
    }



// class FkyLoad
// {

//    /**
//     * [loadf 加载函数]
//     * @return [type] [description]
//     */
//     public static function loadf() {
//         $arguments = func_get_args();//获取传给函数的参数（数组）
//         $name = array_shift($arguments);//弹出第一个参数，即函数名
//         if ($name == '') {
//             die('function name is empty!');
//         } else {
//             $call_exist = stripos($name, 'call:');//如果有call:字样，就直接返回函数名
//             if ($call_exist === 0) {
//                $callf = explode(':', $name);
//                $name = $callf[1];
//             }
//             $func =  dirname(__FILE__).DIRECTORY_SEPARATOR.'func'.DIRECTORY_SEPARATOR. strtolower($name) . '.func.php';

//             if (!is_file($func)) {
//                 die(' function ' . $name . ' Not Found!');
//             }
//             require_once $func;
//          $function = 'fky'.DIRECTORY_SEPARATOR.'func'.DIRECTORY_SEPARATOR.$name;

//             if ($call_exist === 0) {
//                 return $function;
//             } else {
//                 return  call_user_func_array ($function , $arguments);//调用函数，并传递参数
//             }
//         }
//     }
//     /*
//     加载类
//     */
//     public static function loadc() {
//         $arguments = func_get_args();//获取传给函数的参数（数组）
//         $name = array_shift($arguments);//弹出第一个参数，即类名
//         if ($name == '') {
//             die('class name is empty!');
//         }
//         static $fky_modules = array();
//         if (isset($fky_modules[$name])) {
//             return $fky_modules[$name];
//         }
//         $class =  dirname(__FILE__).DIRECTORY_SEPARATOR.'classs'.DIRECTORY_SEPARATOR. strtolower($name) . '.class.php';

//         if (!is_file($class)) {
//             die(' class ' . $name . ' Not Found!');
//         }
//         require_once $class;
//         $class_name = 'fky'.DIRECTORY_SEPARATOR.'classs'.DIRECTORY_SEPARATOR.ucfirst($name);

//         $class_name = new \ReflectionClass($class_name);//反射类
//         $fky_modules[$name] = $class_name->newInstanceArgs($arguments);//传入参数
//         return $fky_modules[$name];
//     }


//     //当调用了没有的方法时，自动调用这个方法
//     /**
//      * [__call 加载函数]
//      * @param  [type] $name      [函数名]
//      * @param  [type] $arguments [参数数组]
//      * @return [type]            [description]
//      */
//     public function __call($name, $arguments) {//调用的函数名，参数数组
//         $func =  dirname(__FILE__).DIRECTORY_SEPARATOR.'func'.DIRECTORY_SEPARATOR. strtolower($name) . '.func.php';

//         if (!is_file($func)) {
//             die(' function ' . $name . ' Not Found!');
//         }
//         require_once $func;
//         $function = 'fky'.DIRECTORY_SEPARATOR.'func'.DIRECTORY_SEPARATOR.$name;

//         if (count($arguments) > 0) {
//             return  call_user_func_array ($function , $arguments);//调用函数，并传递参数
//         } else {
//             return $function;//如果没有参数，就直接返回函数名
//         }

//     }

   
//     //当调用的静态方法不存在时，会自动调用__callStatic方法
//     /**
//      * [__callStatic 加载类]
//      * @param  [type] $name      [调用的类名]
//      * @param  [type] $arguments [参数数组]
//      * @return [type]            [description]
//      */
//     public static function __callStatic($name, $arguments) {//调用的类名，参数数组
//         static $fky_modules = array();
//         if (isset($fky_modules[$name])) {
//             return $fky_modules[$name];
//         }
//         $class =  dirname(__FILE__).DIRECTORY_SEPARATOR.'classs'.DIRECTORY_SEPARATOR. strtolower($name) . '.class.php';

//         if (!is_file($class)) {
//             die(' class ' . $name . ' Not Found!');
//         }
//         require_once $class;
//         $class_name = 'fky'.DIRECTORY_SEPARATOR.'classs'.DIRECTORY_SEPARATOR.ucfirst($name);

//         $class_name = new \ReflectionClass($class_name);//反射类
//         $fky_modules[$name] = $class_name->newInstanceArgs($arguments);//传入参数
//         return $fky_modules[$name];
//     }

// }

