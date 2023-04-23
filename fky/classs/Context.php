<?php
namespace fky\classs;

/**
 * 上下文存储
 * Class Context
 * @package fky\classs
 */
class Context
{
    // 上下文数据
    protected static $contents = [];

    // 默认作用域
    private static $defaultScope = '_default_';

    /**
     * 设置上下文
     * @param string $key
     * @param mixed  $value
     * @param string $scope 作用域
     */
    public static function set(string $key, $value, string $scope = '')
    {
        $scope = self::getScope($scope);

        self::setVal($key, $value, $scope);
    }

    /**
     * 批量设置上下文
     * @param array  $pairs 键值对
     * @param string $scope 作用域
     */
    public static function multiSet(array $pairs, string $scope = '')
    {
        $scope = self::getScope($scope);
        foreach ($pairs as $key => $value) {
            self::setVal($key, $value, $scope);
        }
    }

    /**
     * 获取指定上下文
     * @param string     $key
     * @param mixed|null $default 默认值
     * @param string     $scope   作用域
     * @return mixed
     */
    public static function get(string $key, $default = null, string $scope = '')
    {
        $keys = explode('.', $key);
        $contents = self::getScopeContents($scope);
        foreach ($keys as $key) {
            if (!isset($contents[$key])) return $default;
            $contents = $contents[$key];
        }
        return ($contents !== null && $contents !== '') || is_null($default) ? $contents : $default;
    }

    /**
     * 检查指定上下文是否存在
     * @param string $key
     * @param string $scope 作用域
     * @return bool
     */
    public static function has(string $key, string $scope = ''): bool
    {
        $keys = explode('.', $key);
        $contents = self::getScopeContents($scope);
        foreach ($keys as $key) {
            if (!isset($contents[$key])) return false;
            $contents = $contents[$key];
        }
        return true;
    }

    /**
     * 获取指定作用域所有上下文
     * @param string $scope 作用域
     * @return array
     */
    public static function all(string $scope = ''): array
    {
        return self::getScopeContents($scope);
    }

    /**
     * 删除指定上下文数据
     * @param string $key
     * @param string $scope 作用域
     */
    public static function delete(string $key, string $scope = '')
    {
        if (self::has($key, $scope)) {
            $scope = $scope ?: self::getScope($scope);
            $content = &self::$contents[$scope];
            $keys = explode('.', $key);
            $count = count($keys);
            foreach ($keys as $idx => $key) {
                if ($idx !== $count - 1) {
                    $content = &$content[$key];
                    continue;
                }
                unset($content[$key]);
            }
        }
    }

    /**
     * 清除指定作用域数据
     * @param string $scope 作用域
     */
    public static function clear(string $scope = '')
    {
        $scope = self::getScope($scope);
        if (isset(self::$contents[$scope])) {
            self::$contents[$scope] = [];
        }
    }

    /**
     * 获取作用域
     * @param string $scope 作用域
     * @return string
     */
    public static function getScope(string $scope = ''): string
    {
        return $scope !== '' ? $scope : self::$defaultScope;
    }

    /**
     * 获取指定作用域所有数据
     * @param string $scope
     * @return mixed
     */
    public static function getScopeContents(string $scope)
    {
        $scope = self::getScope($scope);

        return self::$contents[$scope] ?? null;
    }

    /**
     * 设置上下文值
     * @param string $key   键名（支持多级stu.name）
     * @param mixed  $value 键值
     * @param string $scope 作用域
     */
    public static function setVal(string $key, $value, string $scope)
    {
        if (!isset(self::$contents[$scope])) {
            self::$contents[$scope] = [];
        }

        $contents = & self::$contents[$scope];
        self::doSetVal($key, $value, $contents);
    }

    /**
     * K-V形式设置
     * @param string $key   键名（支持多级stu.name）
     * @param mixed  $value 键值
     * @param array  $data  目标存储变量
     */
    public static function doSetVal(string $key, $value, array &$data)
    {
        $keys = explode('.', $key);
        $count = count($keys) - 1;
        foreach ($keys as $idx => $subkey) {
            // 赋值操作
            if ($subkey == '[]') {
                $data[] = $value;
                break;
            } elseif ($idx === $count) {
                $data[$subkey] = $value;
                break;
            }
            // 检查键名是否存在
            if (!key_exists($subkey, $data)) {
                $data[$subkey] = [];
            }
            // 移动游标
            $data = &$data[$subkey];
        }
    }
}