<?php

namespace fky\func;

/**
 * curl post方法请求，支持传输文件
 * @param    [type]     $url   [请求的地址]
 * @param    array $assoc [请求的参数字段数组]
 * @param    array $files [请求的文件地址数组]
 * @return   [type]            [description]
 */
function curlPostFields($url, array $assoc = array(), array $files = array())
{
    static $disallow = array("\0", "\"", "\r", "\n");
    foreach ($assoc as $k => $v) {
        $k = str_replace($disallow, "_", $k);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"",
            "",
            filter_var($v),//过滤变量，去除标签，去除编码特殊字符。
        ));
    }

    if (!empty($files)) {
        foreach ($files as $k => $v) {
            ini_set('user_agent', "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
            $data = @file_get_contents($v);
            $k = str_replace($disallow, "_", $k);
            $v = str_replace($disallow, "_", $v);
            $body[] = implode("\r\n", array(
                "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
                "Content-Type: application/octet-stream",
                "",
                $data,
            ));
        }
    }
    do {
        $boundary = "---------------------" . md5(mt_rand() . microtime());
    } while (preg_grep("/{$boundary}/", $body));

    // add boundary for each parameters
    array_walk($body, function (&$part) use ($boundary) {
        $part = "--{$boundary}\r\n{$part}";
    });

    // add final boundary
    $body[] = "--{$boundary}--";
    $body[] = "";
    $ch1 = curl_init();

    curl_setopt($ch1, CURLOPT_URL, $url);
    curl_setopt($ch1, CURLOPT_POST, 1);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);

    // set options
    @curl_setopt_array($ch1, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => implode("\r\n", $body),
        CURLOPT_HTTPHEADER => array(
            "Expect: 100-continue",
            "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
        ),
    ));

    $result = curl_exec($ch1);
    curl_close($ch1);

    return $result;
}