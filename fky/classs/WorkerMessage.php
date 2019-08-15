<?php
namespace fky\classs;
use fky\classs\exceptions\WorkerMessageInvalidException;
/**
 * worker消息封装
 */
class WorkerMessage
{
    //worker类型
    private $_workerType = '';
    //worker参数
    private $_params = [];

    public function __construct($srcData = '')
    {
        if (!empty($srcData)) {
            $this->unSerialize($srcData);
        }
    }

    /**
     * @return mixed
     */
    public function getWorkerType()
    {
        return $this->_workerType;
    }

    /**
     * @param mixed $workerType
     */
    public function setWorkerType($workerType)
    {
        $this->_workerType = $workerType;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * 序列化
     * @return string
     */
    public function serialize()
    {
        $data = [
            'workerType' => $this->_workerType,
            'params' => $this->_params
        ];
        return json_encode($data);
    }

    /**
     * 返序列化
     * @param string $srcData      - 原始数据
     * @throws \Exception
     */
    public function unSerialize($srcData)
    {
        if (empty($srcData)) {
            throw new WorkerMessageInvalidException("worker msg is empty");
        }
        $data = json_decode($srcData, true);
        if (empty($data)) {
            throw new WorkerMessageInvalidException("WorkerMessage invalid. data={$srcData}");
        }
        if (!isset($data['workerType']) || !isset($data['params'])) {
            throw new WorkerMessageInvalidException("WorkerMessage invalid. data={$srcData}");
        }
        $this->setWorkerType($data['workerType']);
        $this->setParams($data['params']);
    }
}