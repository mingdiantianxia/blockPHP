<?php
namespace fky\classs;

/**
 * 数据传输对象，用于逻辑或数据层间的结果返回
 * @method \fky\classs\Dto success($data = [], $msg = '') static 成功响应
 * @method \fky\classs\Dto false($data = [], $msg = '') static 失败响应
 * @method \fky\classs\Dto error($msg = '', $data = []) static 错误响应
 * @author fukaiyao
 */
class Dto
{
    //结果码
    private $_code = null;

    //提示语
    private $_msg = null;

    //数据
    private $_data = null;

    //方法列表和对应的结果级别
    private $_level = [
        'success' => 0, //成功
        'error'   => 1, //错误
        'false'   => 2, //失败
    ];

    /**
     * 设置数据
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * 获取数据
     * @return $this
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * 设置code
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * 获取code
     * @return $this
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * 设置提示语
     * @param $msg
     * @return $this
     */
    public function setMsg($msg)
    {
        $this->_msg = $msg;
        return $this;
    }

    /**
     * 获取提示语
     * @return $this
     */
    public function getMsg()
    {
        return $this->_msg;
    }

    /**
     * 获取所有响应属性值
     * @return array
     */
    public function getResponseInfo()
    {
        return [
            'code' => $this->_code,
            'message' => $this->_msg,
            'data' => $this->_data,
        ];
    }

    /**
     ************************ 以下只对约定俗成的属性值定义方法 **************
     */

    /**
     * 判断否是正确
     * @return bool
     */
    public function isSuccess()
    {
        return $this->_level['success'] == $this->_code;
    }

    /**
     * 判断是否错误
     * @return bool
     */
    public function isError()
    {
        return $this->_level['error'] == $this->_code;
    }

    /**
     * 判断是否失败
     * @return bool
     */
    public function isFalse()
    {
        return $this->_level['false'] == $this->_code;
    }

    /**
     * 转换为正确结果码
     * @param mixed $data -数据
     * @param string $msg -提示语
     * @return $this
     */
    public function toSuccess($data = [],$msg = '')
    {
        $this->_code = $this->_level['success'];

        $this->_msg = $msg;
        $this->_data = $data;

        return $this;
    }

    /**
     * 转换为失败结果码
     * @param mixed $data -数据
     * @param string $msg -提示语
     * @return $this
     */
    public function toFalse($data = [],$msg = '')
    {
        $this->_code = $this->_level['false'];

        $this->_msg = $msg;
        $this->_data = $data;

        return $this;
    }

    /**
     * 转换为错误结果码
     * @param string $msg -提示语
     * @param mixed $data -数据
     * @return $this
     */
    public function toError($msg = '', $data = [])
    {
        $this->_code = $this->_level['error'];

        $this->_msg = $msg;
        $this->_data = $data;

        return $this;
    }

    /**
     * 静态方法调用
     * @access public
     * @param  string $method 调用方法
     * @param  mixed  $args   参数
     * @return static
     */
    public static function __callStatic($method, $args)
    {
        $response = new static();

        if ('error' == $method) {
            call_user_func_array([$response, 'to' . ucfirst($method)], $args);
            return $response;
        }

        array_unshift($args, $method);
        call_user_func_array([$response, '_make'], $args);
        return $response;
    }

    /**
     * 调用方法，赋值属性
     * @param $method -方法名（详细看状态级别）
     * @param mixed $data -数据
     * @param string $msg -提示语
     */
    private function _make($method, $data = [], $msg = '')
    {
        //从方法级别中设置结果码
        $this->_code = $this->_level[$method];

        $this->_msg = $msg;
        $this->_data = $data;
    }

}