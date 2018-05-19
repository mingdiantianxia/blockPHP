<?php
namespace fky\func;
//定义输入检查函数
/**
 * Xss过滤
 * @param string|array $input        要过滤的内容
 * @param bool $tags    去除脚本标签
 * @param bool $trim    去除两边空格
 * @return mixed|null|string|string[]
 */
function XssFilter($input, $tags = true, $trim = true) {
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