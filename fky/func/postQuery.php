<?php

namespace fky\func;

/**
 * 发起Http 请求
 * @param $url -请求的地址
 * @param $field -字段参数
 * @return bool|string
 */
function postQuery($url, array $field)
{
    $options['http'] = ['timeout'=> 60, 'method' => 'POST', 'header' => 'Content-type:application/json', 'content' => json_encode($field)];

    $context = stream_context_create($options);

    $result  = file_get_contents($url, false, $context);

    return json_decode($result,true);
}