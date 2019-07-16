<?php
namespace fky\traits;
/**
 * Created by PhpStorm.
 * User: fukaiyao
 * Date: 2018/4/26/026
 * Time: 11:08
 */
trait BaseTool {
    use Response;
    /**
     * @author fukaiyao
     * Xss过滤
     * @param string|array $input        要过滤的内容
     * @param bool $tags    去除脚本标签
     * @param bool $trim    去除两边空格
     * @return mixed|null|string|string[]
     */
    public function XssFilter($input, $tags = true, $trim = true) {
        if (is_array($input)) {
            foreach($input as $key => &$value)
            {
                if (is_array($value)) {
                    $value = $this->XssFilter($value);
                } else {
                    //去除字符串中两边多余的空格，剥去HTML、XML以及PHP的标签，转为html实体
                    if ($trim) {
                        $value = trim($value);
                    }
                    if ($tags) {
                        //替换脚本字样的字符串
                        $value = strip_tags($value);
                        $value = str_replace(array('<?', '<%', '<?php', '{php'), '', $value);
                        $value = preg_replace('/<s*?script.*(src)+/i', '', $value);
                    }
                    $value = htmlspecialchars($value);
                }

            }
        } else {
            if ($trim) {
                $input = trim($input);
            }
            if ($tags) {
                $input = strip_tags($input);
                $input = str_replace(array('<?', '<%', '<?php', '{php'), '', $input);
                $input = preg_replace('/<s*?script.*(src)+/i', '', $input);
            }
            $input = htmlspecialchars($input);
        }
        return $input;
    }
    /**
     * @author fukaiyao
     * 生成随机字符串
     * @param int $length    字符长度
     * @return string
     */
    public function createRandomStr($length){
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//62个字符
        $strlen = 62;
        while($length > $strlen){
            $str .= $str;
            $strlen += 62;
        }
        $str = str_shuffle($str);
        return substr($str,0,$length);
    }
    /**
     * @author fukaiyao
     * 生成后台签名
     * @param array $paramArray   加密参数数组
     * @return string
     */
    public function createOpenSign($paramArray,$secret){
        if (!is_array($paramArray)) return false;
        ksort($paramArray, SORT_STRING);
        $str = http_build_query($paramArray);//会自动urlencode
        $sign = strtoupper(md5(md5($str).$secret));
    }

    /**
     * 获取post和get的参数
     * @author fukaiyao
     * @param $param      要获取的参数名
     * @param $default    单项默认值
     * @param $filter     是否xss过滤
     * @return int|string|array|bool
     */
    public function GPC($param = '', $default = '', $filter = true){
        $_GPC = false;
        if (empty($param)) {
            $_GPC = $_GET;
            if (!empty($_POST)) {
                foreach ($_POST as $item => $key) {
                    $_GPC[$item] = $key;
                }
            }
        }
        elseif (isset($_GET[$param]) || isset($_POST[$param])) {
            if (isset($_GET[$param])) {
                $_GPC = $_GET[$param];
            }
            if (isset($_POST[$param])) {
                $_GPC = $_POST[$param];
            }
        }
        else {
           return $default;
        }

        if ($filter) {
            return $this->XssFilter($_GPC);
        } else {
            return $_GPC;
        }

    }

    /**
     * 获取post参数
     * @author fukaiyao
     * @param $param      要获取的参数名
     * @param $default    单项默认值
     * @param $filter     是否xss过滤
     * @return int|string|array|bool
     */
    public function getPost($param = '', $default = '', $filter = true){
        $temp = false;
        if (empty($param)) {
            $temp = $_POST;
        }
        elseif (isset($_POST[$param])) {
            $temp = $_POST[$param];
        }
        else {
            return $default;
        }

        if ($filter) {
            return $this->XssFilter($temp);
        } else {
            return $temp;
        }

    }

    /**
     * 获取get参数
     * @author fukaiyao
     * @param $param      要获取的参数名
     * @param $default    单项默认值
     * @param $filter     是否xss过滤
     * @return int|string|array|bool
     */
    public function getQuery($param = '', $default = '', $filter = true){
        $temp = false;
        if (empty($param)) {
            $temp = $_GET;
        }
        elseif (isset($_POST[$param])) {
            $temp = $_GET[$param];
        }
        else {
            return $default;
        }

        if ($filter) {
            return $this->XssFilter($temp);
        } else {
            return $temp;
        }

    }
}