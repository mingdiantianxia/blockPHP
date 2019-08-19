<?php
namespace fky\classs;
require_once __DIR__.'/../vendor/autoload.php';

use Elasticsearch\ClientBuilder;
/**
 * ES搜索数据层
 */
class ElasticSearch
{
    /**
     * @var ElasticSearchClient
     */
    protected $_client;

    /**
     * @var 配置文件
     */
    protected $_conf;

    public function __construct()
    {
        $this->_conf = $config = loadc('config')->get('address', "elasticsearch"); //获取es的配置信息

        // $logger = ClientBuilder::defaultLogger('./runtime/elasticsearch.log');

        $this->_client = ClientBuilder::create()           // Instantiate a new ClientBuilder
                         // ->setLogger($logger)
                         ->setHosts($this->_conf)      // Set the hosts
                         ->build();
    }

    /**
     * 创建索引
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function AddIndex($params)
    {
        return $this->_client->indices()->create($params);
    }

    /**
     * 更新索引字段类型
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function updateMapping($params)
    {
        return $this->_client->indices()->putMapping($params);
    }

    /**
     * 删除索引
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function deleteIndex($params)
    {
        return $this->_client->indices()->delete($params);
    }

    /**
     * 获取一个索引的设置
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function getIndexSetting($params)
    {
        return $this->_client->indices()->getSettings($params);
    }

    /**
     * 获取文档
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function getDocument($params)
    {
        return $this->_client->get($params);
    }

    /**
     * 搜索文档
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function searchDocument($params)
    {
        return $this->_client->search($params);
    }

    /**
     * 添加索引文档
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function AddIndexDocument($params)
    {
        return $this->_client->index($params);
    }

    /**
     * 更新索引文档
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function updateIndexDocument($params)
    {
        return $this->_client->update($params);
    }

    /**
     * 批量增删改
     * @Author   xiangwenbing
     * @param   array  $params                索引文档参数
     */
    public function bulkDocuments($params)
    {
        return $this->_client->bulk($params);
    }
}
