<?php
namespace fky\classs;
// 一致性哈希算法
class ConsistentHash
{
    // server列表
    private $_server_list = array();

    // 节点列表
    private $_nodes = array();

    // 延迟排序，因为可能会执行多次addServer
    private $_layze_sorted = FALSE;

    protected $mul = 20;  // 每个节点对应20个虚拟节点

    //字符串的计算哈希值
    public function myHash($str)
    {
        // hash(i) = hash(i-1) * 33 + str[i]
        $hash = 0;
        $s = md5($str);
        $seed = 5;
        $len = 32;
        for ($i = 0; $i < $len; $i++) {
            // (hash << 5) + hash 相当于 hash * 32
            //$hash = sprintf("%u", $hash * 32) + ord($s{$i});
            //$hash = ($hash * 32 + ord($s{$i})) & 0x7FFFFFFF;
            //“<< ”$hash各二进位全部左移$seed位，相当于乘以一个(2^$seed)数。不同的是，左移运算可能会产生一个负数（第一位是符号位）
            $hash = ($hash << $seed) + $hash + ord($s{$i});
        }
        return $hash & 0x7FFFFFFF;//long int的最大值,与其进行按位与运算（只有两位都为1才是1），保证获取一个正数（第一位是0为正数）
    }

    /**
     *添加新服务器
     * @param $server -服务器地址
     * @return $this -当前实例
     */
    public function addServer($server)
    {
        $hash = $this->myHash($server);

        //添加新服务器，则初始化排序
        $this->_layze_sorted = FALSE;

        if (!isset($this->_server_list[$server])) {
            // 添加节点和虚拟节点
            for ($i = 0; $i < $this->mul; $i++) {
                $hash = $this->myHash($server . '-' . $i);
                $this->_nodes[$hash] = $server;//一个服务器对应多个虚拟节点
                $this->_server_list[$server][] = $hash;
            }
        }

        return $this;
    }

    //删除服务器
    public function delServer($server)
    {
        if (!isset($this->_server_list[$server])) return false;

        // 循环删除虚拟节点
        foreach ($this->_server_list[$server] as $val) {
            unset($this->_nodes[$val]);
        }

        // 删除节点
        unset($this->_server_list[$server]);

        //删除服务器，则初始化排序
        $this->_layze_sorted = FALSE;

        return $this;
    }


    //查找key对应的服务器
    public function find($key)
    {
        // 虚拟节点排序
        if (!$this->_layze_sorted) {
            ksort($this->_nodes, SORT_REGULAR);
            $this->_layze_sorted = TRUE;
        }

        $hash = $this->myHash($key);
        $len = sizeof($this->_nodes);
        if ($len == 0) {
            return FALSE;
        }

        $keys = array_keys($this->_nodes);//hash值数组
        $values = array_values($this->_nodes);//对应的服务器数组

        // 如果不在hash区间内，则返回第一个server
        if ($hash <= $keys[0] || $hash > $keys[$len - 1]) {
            return $values[0];
        }

        foreach ($keys as $key => $pos) {
            // 区间判断
            if ($hash <= $pos) {
                return $values[$key];
            }
        }

    }

    //获取节点数组
    public function getNodes()
    {
        return $this->_nodes;
    }
}