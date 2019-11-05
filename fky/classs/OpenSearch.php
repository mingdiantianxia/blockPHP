<?php
namespace fky\classs;
require_once __DIR__.'/../inc/OpenSearch/Autoloader/Autoloader.php';
use fky\classs\Log as Logger;
use OpenSearch\Client\OpenSearchClient;
use OpenSearch\Client\SearchClient;
use OpenSearch\Util\SearchParamsBuilder;

/**
 * opensearch基础类
 */
abstract class OpenSearch
{
    /**
     * @var OpenSearchClient
     */
    protected $_client;

    /**
     * 开放搜索client
     * @var SearchClient
     */
    protected $_searchClient;

    /**
     * opensearch配置信息
     */
    protected $_conf;

    /**
     * opensearch 应用名
     * @var string
     */
    protected $_appName = '';

    public function __construct()
    {
        $this->_conf = Config::getInstance()->get('', 'opensearch');
        if (empty($this->_conf)) {
            throw new \Exception('opensearch config not found.');
        }
        $options = array('debug' => false, 'connectTimeout' => 5, 'timeout' => 5);
        $this->_client = new OpenSearchClient($this->_conf['accessKeyId'], $this->_conf['accessSecret'], $this->_conf['endPoint'], $options);
        $this->_searchClient = new SearchClient($this->_client);
    }

    /**
     * 执行opensearch查询
     * @param SearchParamsBuilder $searchParams
     * @param bool $isAllResult - 是否返回opensearch原始字段值
     * @return null | array
     * 成功返回
     * [
     *      'total'     => '总记录数',
     *      'num'       => '当前请求返回的记录数',
     *      'items'     => [id数组],
     *      'facet'     => [
     *                          [
     *                              "key"   => '分组字段',
     *                              "items" => [
     *                                          ['value' => '分组字段值', '聚合函数名(count | max | min | avg)' => '函数值'],
     *                                          ['value' => '分组字段值', '聚合函数名(count | max | min | avg)' => '函数值'],
     *                                      ]
     *                          ]
     *                      ]
     * ]
     */
    protected function execute($searchParams, $isAllResult = false) {
        if (empty($searchParams)) {
            return null;
        }
        $searchParams->setAppName($this->_appName);
        $searchParams->setFormat('fulljson');
        $paramBuild = $searchParams->build();
        Logger::info("opensearch request, query={$paramBuild->query}, filter={$paramBuild->filter}, config=". json_encode($paramBuild->config));
        $ret = $this->_searchClient->execute($searchParams->build());
        $result = json_decode($ret->result, true);if(isset($_GET['dg']))
        if (empty($result)) {
            Logger::error("opensearch request result decode failure. result={$ret->result}");
            return null;
        }
        if ($result['status'] != 'OK') {
            Logger::error("opensearch request error. result={$ret->result}, query={$paramBuild->query}, filter={$paramBuild->filter}, config=" .json_encode($paramBuild->config));
            return null;
        }
        $facet = [];
        if (isset($result['result']['facet']) && !empty($result['result']['facet'])) {
            $facet = $result['result']['facet'];
        }
        $retItems = [];
        if ($isAllResult) {
            $retItems = array_map(function($item) {
                return $item['fields'];
            }, $result['result']['items']);
        }
        else {
            $retItems = array_map(function ($item) {
                return $item['fields']['id'];
            }, $result['result']['items']);
        }
        return  [
            'total' => $result['result']['total'],
            'num'   => $result['result']['num'],
            'items' => $retItems,
            'facet' => $facet,
        ];
    }

}