<?php
namespace app\common\base;

/**
 * 业务类工厂
 * Class serviceFactory
 * @author fukaiyao 2019-11-23 16:33:10
 */
class ServiceFactory
{
    /**
     * 业务实现映射
     *
     * @var array
     */
    private static $_srvImpls = null;

    /**
     * 业务对象缓存
     *
     * @var array
     */
    private static $_services = array();

    //应用根目录
    private static $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

    //根目录命名空间
    private static $baseNamespace = '';

    //目录匹配字符串（用于匹配目录，一次性使用）
    private static $_dirMatchedStr = '';

    //根目录下的类目录
    private static $_classDirArr = null;

    /**
     * 初始化业务映射配置
     *
     * @throws \Exception
     */
    private static function initServiceConfig()
    {
        $srvConfigPath = self::$baseDir . 'services/SrvImpsMapper.php';
        if (!file_exists($srvConfigPath)) {
            throw new \Exception("services config not found, please init services's config");
        }
        // 加载业务实现映射
        self::$_srvImpls = require $srvConfigPath;
    }


    //初始化类目录
    private static function initClassDir()
    {
        self::$_classDirArr = array(
            array(
                'dir' => 'fky' . DIRECTORY_SEPARATOR . 'classs',
                'suffix' => '',//后缀
            )
        );
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
     * 加载类，lc('类名','参数1','参数2'……'参数n')
     * @return mixed
     * @throws \Exception
     * @throws \ReflectionException
     */
    public static function lc()
    {
        if (self::$_classDirArr === null) {
            // 初始化类目录
            self::initClassDir();
        }

        if (self::$_srvImpls === null) {
            // 初始化业务配置
            self::initServiceConfig();
        }

        $dirMatchedStr = self::$_dirMatchedStr;
        self::$_dirMatchedStr = '';//销毁匹配字符

        $arguments = func_get_args();//获取传给函数的参数（数组）
        $name = array_shift($arguments);//弹出第一个参数，即类名
        if ($name == '') {
            throw new \Exception('class name is empty!');
        }

//        $name = strtolower($name);

        //加载类型：1固定目录加载，2业务模块service加载，3直接根据命名空间加载
        $load_type = 1;
        if (stripos($name, '.') !== false) { //根据serviceType命名规则是以“.”隔开的判断，业务模块配置加载
            $load_type = 2;
        }
        elseif (stripos($name, '\\') !== false) { //有命名空间的类名，直接根据命名空间加载
            //统一去除命名空间左边反斜杠
            if (substr($name, 0, 1) == '\\') {
                $name = ltrim($name, '\\');
            }
            $dirMatchedStr = '';
            $load_type = 3;
        }

        //有匹配字符的，拼接匹配字符作为识别缓存名
        $nameSuff = !empty($dirMatchedStr) ? '-' . $dirMatchedStr : '';
        if (isset(self::$_services[$name . $nameSuff])) {
            return self::$_services[$name . $nameSuff];
        }

        self::$baseDir = strtr(self::$baseDir, '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠
        if (substr(self::$baseDir, -1) != DIRECTORY_SEPARATOR) {
            self::$baseDir .= DIRECTORY_SEPARATOR;
        }

        //解析类名，加载类文件
        if ($load_type == 1) {
            $class_name = self::loadClassFromDir($name, $dirMatchedStr);
        }
        elseif ($load_type == 2) {
            $class_name = self::loadClassFromService($name);
        }
        elseif ($load_type == 3) { //有命名空间，直接根据命名空间加载
            $class_name = self::loadClassFromNamespace($name);
        }


        $class_name = new \ReflectionClass($class_name);//反射类
        self::$_services[$name . $nameSuff] = $class_name->newInstanceArgs($arguments);//传入参数
        return self::$_services[$name . $nameSuff];
    }

    /**
     * 创建指定业务对象
     *
     * @param string $serviceType
     *            - SrvType定义的业务类型
     */
    private static function loadClassFromService($serviceType)
    {
        if (self::$_srvImpls === null) {
            // 初始化业务配置
            self::initServiceConfig();
        }

        /**
         * 根据配置映射关系，创建实现类实例。
         */
        if (!isset(self::$_srvImpls[$serviceType])) {
            throw new \Exception("It hasn't such a service type:" . $serviceType);
        }

        $class_name = self::$baseNamespace . '\services\modules' . self::$_srvImpls[$serviceType];

        //加载类文件
        $class = self::$baseDir . 'services\modules' . self::$_srvImpls[$serviceType] . '.php';
        $class = strtr($class, '\\', DIRECTORY_SEPARATOR); // 文件标准路径
        if (!is_file($class)) {
            throw new \Exception(' class ' . $class_name . ' Not Found!');
        }
        require_once $class;


        return $class_name;
    }

    /**
     * 根据命名空间加载
     * @param $className
     * @return string
     * @throws \Exception
     */
    private static function loadClassFromNamespace($className)
    {
        $class_name = self::$baseNamespace . '\\' . $className;
        $class_name = strtr($class_name, DIRECTORY_SEPARATOR, '\\');

        $class = self::$baseDir . $className . '.php';
        $class = strtr($class, '\\', DIRECTORY_SEPARATOR); // 文件标准路径
        if (!is_file($class)) {
            throw new \Exception(' class ' . $class_name . ' Not Found!');
        }
        require_once $class;

        return $class_name;
    }

    /**
     * 根据固定目录加载
     * @param $className
     * @param $dirMatchedStr -用于精确匹配的字符串
     * @return string
     * @throws \Exception
     */
    private static function loadClassFromDir($className, $dirMatchedStr)
    {
        $isFind = 0;
        foreach (self::$_classDirArr as $key => &$classDir) {
            $classDir['dir'] = strtr($classDir['dir'], '/', DIRECTORY_SEPARATOR);//转化反斜杠为标准反斜杠

            //查找目录匹配字符，不匹配则下一个
            if (!empty($dirMatchedStr) && stripos($classDir['dir'], strtr($dirMatchedStr, '/', DIRECTORY_SEPARATOR)) === false) {
                continue;
            }

            if (substr($classDir['dir'], -1) == DIRECTORY_SEPARATOR) {
                $classDir['dir'] = trim($classDir['dir'], DIRECTORY_SEPARATOR);
            }

            //类名后缀
            $suffix = isset($classDir['suffix']) ? $classDir['suffix'] : '';
            $class = self::$baseDir . $classDir['dir'] . DIRECTORY_SEPARATOR . $className . $suffix . '.php';
            if (!is_file($class)) {
                continue;
            }
            $isFind = 1;
            require_once $class;

            $class_name = self::$baseNamespace . '\\' . $classDir['dir'] . '\\' . $className . $suffix;
            $class_name = strtr($class_name, DIRECTORY_SEPARATOR, '\\');
            break;
        }

        if (!$isFind) {
            throw new \Exception(' class ' . $className . ' Not Found!');
        }

        return $class_name;
    }

}

