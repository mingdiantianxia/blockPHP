<?php
/**
 * Opensearch配置
 */
return [
    //阿里云access key id
    'accessKeyId' => '',
    //阿里云access secret
    'accessSecret' => '',
    //阿里云opensearch api域名
    'endPoint' => '',
    //索引app
    'indexApps' => [
        //外卖订单
        'waimaiOrder' => 'lewaimai_order_dev',
        //商品
        'food'  => 'lewaimai_food_dev',
        // 客户搜索
        'lewaimaiCustomer' => 'lewaimai_customer_dev',
        // 会员搜索
        'lewaimaiMemberSearch' => 'lewaimai_member_dev',
    ]
];
