<?php
namespace fky\classs;
/**
    自动加载类
    使用方法
    include_once 'Loader.php'; // 引入加载器
    \fky\classs\Loader::run();
*/
class Loader
{
    //项目根目录
    private static $baseDir = __DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
    /* 路径映射 */
    // public static $vendorMap = array(
    //     'Widi\BlockChain' => __DIR__ . DIRECTORY_SEPARATOR . '..'.DIRECTORY_SEPARATOR.'src',
    // );

    //运行加载器，自动加载
    /**
     * @param mainDir 项目根目录
     */
    public static function run($mainDir=null)
    {
        if (!empty($mainDir)) {
            self::$baseDir = $mainDir;
        }
        spl_autoload_register('\fky\classs\Loader::autoload');// 注册自动加载
    }

    /**
     * 自动加载器
     */
    public static function autoload($class)
    {
        $file = self::findFile($class);
        if (file_exists($file)) {
            self::includeFile($file);
        }
    }

    /**
     * 解析文件路径
     */
    private static function findFile($class)
    {
        // $vendor = substr($class, 0, strpos($class, '\\')); // 顶级命名空间
        // $vendor = 'Widi\BlockChain';
        // $vendorDir = self::$vendorMap[$vendor]; // 文件基目录
        // $filePath = substr($class, strlen($vendor)) . '.php'; // 文件相对路径

        $filePath = self::$baseDir.$class . '.php'; // 文件相对路径
        return strtr($filePath, '\\', DIRECTORY_SEPARATOR); // 文件标准路径
    }

    /**
     * 引入文件
     */
    private static function includeFile($file)
    {
        if (is_file($file)) {
            include_once $file;
        }
    }
}
