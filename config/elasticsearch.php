<?php
/**
 * Opensearch配置
 */
return [
    "address" => [
        'http://elastic:xunxiang125KNGbhga@es-cn-mp9125qxt0006hu0j.elasticsearch.aliyuncs.com:9200',
    ],
    "username" => "elastic",
    "password" => "xunxiang125KNGbhga",
    "indexs" => [
        //客户搜索
        'order_customer_index' => [
            "index"            => "index-cy-order-customernew",
            'type'             => 'type-cy-order-customernew',
        ],
        //商户数据
        'merchant_index'       => [
            "index"            => "index-cy-merchant",
            'type'             => 'type-cy-merchant',
        ],
        //商品数据
        'food_index'           => [
            'index'            => 'index-cy-food',
            'type'             => 'type-cy-food',
        ],
        //会员数据
        'member_index'           => [
            'index'            => 'index-cy-member',
            'type'             => 'type-cy-member',
        ],
    ],
    "indexDefault" => [
        "order_customer_index" => [
            'lewaimai_customer_id'   => ['type' => 'long'],
            'admin_id'               => ['type' => 'long'],
            'is_member'              => ['type' => 'long'],
            'remark'                 => ['type' => 'keyword'],
            'point'                  => ['type' => 'long'],
            'customer_channel'       => ['type' => 'long'],
            'last_order_time'        => ['type' => 'date'],
            'init_time'              => ['type' => 'date'],
            'zhinengji_order_num'    => ['type' => 'long'],
            'shouyintai_order_num'   => ['type' => 'long'],
            'errand_order_num'       => ['type' => 'long'],
            'tangshi_order_num'      => ['type' => 'long'],
            'waimai_order_num'       => ['type' => 'long'],
            'zhinengji_money_count'  => ['type' => 'double'],
            'shouyintai_money_count' => ['type' => 'double'],
            'errand_money_count'     => ['type' => 'double'],
            'tangshi_money_count'    => ['type' => 'double'],
            'waimai_money_count'     => ['type' => 'double'],
            'all_money_count'        => ['type' => 'double'],
            'all_order_num'          => ['type' => 'long'],
            'average_money'          => ['type' => 'double'],
            'sex'                    => ['type' => 'long'],
            'reg_phone'              => ['type' => 'keyword'],
            'birthday'               => ['type' => 'date'],
            'birthyear'              => ['type' => 'long'],
            'name'                   => ['type' => 'text'],
            'nickname'               => ['type' => 'text'],
            'blacklist'              => ['type' => 'long'],
            'address'                => ['type' => 'text'],
            'property_value1'        => ['type' => 'text'],
            'property_value2'        => ['type' => 'text'],
            'first_order_time'       => ['type' => 'date'],
            'totalprice'             => ['type' => 'double'],
            'succeeded_order_num'    => ['type' => 'long'],
        ],
        "merchant_index" => [
            'id'                         => ['type' => 'long'],
            'username'                   => ['type' => 'keyword'],
            'icon'                       => ['type' => 'keyword'],
            'is_weixinauth'              => ['type' => 'long'],
            'wx_name'                    => ['type' => 'keyword'],
            'head_img'                   => ['type' => 'keyword'],
            'wx_account'                 => ['type' => 'keyword'],
            'wx_tousername'              => ['type' => 'keyword'],
            'qrcode_url'                 => ['type' => 'keyword'],
            'wx_type'                    => ['type' => 'long'],
            'phone'                      => ['type' => 'keyword'],
            'email'                      => ['type' => 'keyword'],
            'province'                   => ['type' => 'keyword'],
            'city'                       => ['type' => 'keyword'],
            'area'                       => ['type' => 'keyword'],
            'setuptime'                  => ['type' => 'date'],
            'from_type'                  => ['type' => 'long'],
            'version_type'               => ['type' => 'long'],
            'oem_id'                     => ['type' => 'long'],
            'is_blacklist'               => ['type' => 'long'],
            'agent_id'                   => ['type' => 'long'],
            'bindphone'                  => ['type' => 'long'],
            'bindemail'                  => ['type' => 'long'],
            'member_func'                => ['type' => 'long'],
            'is_system_version_user'     => ['type' => 'long'], //是否是系统版本用户0、不是，1、是
            'system_version_due_date'    => ['type' => 'date'], //系统版本过期时间 默认：0
            'system_version_level'       => ['type' => 'long'], //系统版本等级 默认：-1
            'is_proxy_operation_user'    => ['type' => 'long'], //是否是代运营用户 0、不是，1、是
            'proxy_operation_due_date'   => ['type' => 'date'], //代运营过期时间 默认：0
            'proxy_operation_level'      => ['type' => 'long'], //代运营套餐 默认：array()
            'proxy_operation_waimai_num' => ['type' => 'long'], //代运营外卖营销套餐数量 默认：0
            'proxy_operation_inshop_num' => ['type' => 'long'], //代运营店内效率套餐数量 默认：0
            'proxy_operation_member_num' => ['type' => 'long'], //代运营会员营销套餐数量 默认：0
            'proxy_operation_fans_num'   => ['type' => 'long'], //代运营吸粉拉新套餐数量 默认：0
            'proxy_operation_rebuy_num'  => ['type' => 'long'], //代运营复购激活套餐数量 默认：0
            'is_weixin_features_user'    => ['type' => 'long'], //是否是店铺微信功能用户0、不是，1、是
            'weixin_features_due_date'   => ['type' => 'date'], //微信功能过期时间 默认：0
            'weixin_features_num'        => ['type' => 'long'], //微信功能数量 默认：0
            'is_wxapp_user'              => ['type' => 'long'], //是否是小程序用户0、不是，1、是
            'wxapp_due_date'             => ['type' => 'date'], //小程序过期时间 默认：0
            'wxapp_num'                  => ['type' => 'long'], //小程序功能数量 默认：0
            'is_member_user'             => ['type' => 'long'], //是否是会员用户0、不是，1、是
            'member_due_date'            => ['type' => 'date'], //会员过期时间 默认：0
            'member_init_date'           => ['type' => 'date'], //会员开通时间 默认：0
            'is_redbag_user'             => ['type' => 'long'], //是否是拼手气红包用户0、不是，1、是
            'redbag_due_date'            => ['type' => 'date'], //拼手气红包过期时间 默认：0
            'redbag_init_date'           => ['type' => 'date'], //拼手气红包开通时间 默认：0
            'is_precision_user'          => ['type' => 'long'], //是否是精准营销用户0、不是，1、是
            'precision_due_date'         => ['type' => 'date'], //精准营销过期时间 默认：0
            'precision_init_date'        => ['type' => 'date'], //精准营销开通时间 默认：0
            'is_integral_user'           => ['type' => 'long'], //是否是积分用户0、不是，1、是
            'integral_due_date'          => ['type' => 'date'], //积分过期时间 默认：0
            'integral_init_date'         => ['type' => 'date'], //积分开通时间 默认：0
            'is_checkin_user'            => ['type' => 'long'], //是否是签到用户0、不是，1、是
            'checkin_due_date'           => ['type' => 'date'], //签到过期时间 默认：0
            'checkin_init_date'          => ['type' => 'date'], //签到开通时间 默认：0
            'is_turntable_user'          => ['type' => 'long'], //是否是大转盘用户0、不是，1、是
            'turntable_due_date'         => ['type' => 'date'], //大转盘过期时间 默认：0
            'turntable_init_date'        => ['type' => 'date'], //大转盘开通时间 默认：0
            'is_invitegift_user'         => ['type' => 'long'], //是否是邀请有礼用户0、不是，1、是
            'invitegift_due_date'        => ['type' => 'date'], //邀请有礼过期时间 默认：0
            'invitegift_init_date'       => ['type' => 'date'], //邀请有礼开通时间 默认：0
            'is_fightgroup_user'         => ['type' => 'long'], //是否是拼团用户0、不是，1、是
            'fightgroup_due_date'        => ['type' => 'date'], //拼团过期时间 默认：0
            'fightgroup_init_date'       => ['type' => 'date'], //拼团开通时间 默认：0
            'is_bargain_user'            => ['type' => 'long'], //是否是砍价用户0、不是，1、是
            'bargain_due_date'           => ['type' => 'date'], //砍价过期时间 默认：0
            'bargain_init_date'          => ['type' => 'date'], //砍价开通时间 默认：0
            'is_scratch_user'            => ['type' => 'long'], //是否是刮刮乐用户0、不是，1、是
            'scratch_due_date'           => ['type' => 'date'], //刮刮乐过期时间 默认：0
            'scratch_init_date'          => ['type' => 'date'], //刮刮乐开通时间 默认：0
            'is_ccard_user'              => ['type' => 'long'], //是否是次卡用户0、不是，1、是
            'ccard_due_date'             => ['type' => 'date'], //次卡过期时间 默认：0
            'ccard_init_date'            => ['type' => 'date'], //次卡开通时间 默认：0
            'is_giftcard_user'           => ['type' => 'long'], //是否是礼品卡用户0、不是，1、是
            'giftcard_due_date'          => ['type' => 'date'], //礼品卡过期时间 默认：0
            'giftcard_init_date'         => ['type' => 'date'], //礼品卡开通时间 默认：0
            'is_shake_user'              => ['type' => 'long'], //是否是摇一摇用户0、不是，1、是
            'shake_due_date'             => ['type' => 'date'], //摇一摇过期时间 默认：0
            'shake_init_date'            => ['type' => 'date'], //摇一摇开通时间 默认：0
            'is_goldenegg_user'          => ['type' => 'long'], //是否是砸金蛋用户0、不是，1、是
            'goldenegg_due_date'         => ['type' => 'date'], //砸金蛋过期时间 默认：0
            'goldenegg_init_date'        => ['type' => 'date'], //砸金蛋开通时间 默认：0
            'is_microalbum_user'         => ['type' => 'long'], //是否是微相册用户0、不是，1、是
            'microalbum_due_date'        => ['type' => 'date'], //微相册过期时间 默认：0
            'microalbum_init_date'       => ['type' => 'date'], //微相册开通时间 默认：0
            'is_rechargecard_user'       => ['type' => 'long'], //是否是充值卡用户0、不是，1、是
            'rechargecard_due_date'      => ['type' => 'date'], //充值卡过期时间 默认：0
            'rechargecard_init_date'     => ['type' => 'date'], //充值卡开通时间 默认：0
            'is_equitycard_user'         => ['type' => 'long'], //是否是权益卡用户0、不是，1、是
            'equitycard_due_date'        => ['type' => 'date'], //权益卡过期时间 默认：0
            'equitycard_init_date'       => ['type' => 'date'], //权益卡开通时间 默认：0
            'is_shoppingcard_user'       => ['type' => 'long'], //是否是购物卡用户0、不是，1、是
            'shoppingcard_due_date'      => ['type' => 'date'], //购物卡过期时间 默认：0
            'shoppingcard_init_date'     => ['type' => 'date'], //购物卡开通时间 默认：0
        ],
        "food_index" => [
            'id'                       => ['type'=>'long'],
            'admin_id'                 => ['type'=>'long'],
            'shop_id'                  => ['type'=>'long'],
            'name'                     => ['type'=>'text'],
            'unit'                     => ['type' => 'keyword'],
            'label'                    => ['type' => 'text'],
            'tag'                      => ['type' => 'long'],
            'type_id'                  => ['type' => 'long'],
            'second_type_id'           => ['type' => 'long'],
            'price'                    => ['type' => 'double'],
            'buying_price'             => ['type' => 'double'],
            'img'                      => ['type' => 'keyword'],
            'small_img'                => ['type' => 'keyword'],
            'description'              => ['type' => 'text'],
            'point'                    => ['type' => 'long'],
            'ordered_count'            => ['type' => 'long'],
            'is_nature'                => ['type' => 'long'],
            'status'                   => ['type' => 'keyword'],
            'stockvalid'               => ['type' => 'long'],
            'stock'                    => ['type' => 'long'],
            'is_limitfood'             => ['type' => 'long'],
            'start_time'               => ['type' => 'date'],
            'stop_time'                => ['type' => 'date'],
            'foodnum'                  => ['type' => 'long'],
            'has_formerprice'          => ['type' => 'long'],
            'formerprice'              => ['type' => 'double'],
            'memberlimit'              => ['type' => 'long'],
            'open_autostock'           => ['type' => 'long'],
            'autostocknum'             => ['type' => 'long'],
            'member_price_used'        => ['type' => 'long'],
            'member_price'             => ['type' => 'double'],
            'totalsales'               => ['type' => 'long'],
            'todaysales'               => ['type' => 'long'],
            'lastsaletime'             => ['type' => 'date'],
            'is_dabao'                 => ['type' => 'long'],
            'dabao_money'              => ['type' => 'double'],
            'supporttype'              => ['type' => 'long'],
            'is_day_limitfood'         => ['type' => 'long'],
            'day_foodnum'              => ['type' => 'long'],
            'barcode'                  => ['type' => 'keyword'],
            'stock_warning'            => ['type' => 'long'],
            'waimai_ordered_count'     => ['type' => 'long'],
            'tangshi_ordered_count'    => ['type' => 'long'],
            'diancan_ordered_count'    => ['type' => 'long'],
            'saoma_ordered_count'      => ['type' => 'long'],
            'chengzhong_ordered_count' => ['type' => 'double'],
            'zhengcan_ordered_count'   => ['type' => 'long'],
            'descript'                 => ['type' => 'text'],
            'is_open_discount'         => ['type' => 'long'],
            'discount_count'           => ['type' => 'long'],
            'discount_value'           => ['type' => 'long'],
            'is_waimai_show'           => ['type' => 'long'],
            'is_tangshi_show'          => ['type' => 'long'],
            'is_shouyinji_show'        => ['type' => 'long'],
            'is_zhengcan_show'         => ['type' => 'long'],
            'pungency_level'           => ['type' => 'long'],
            'is_set_feature'           => ['type' => 'long'],
            'set_feature_desc'         => ['type' => 'text'],
            'cook_time'                => ['type' => 'long'],
            'limit_tags'               => ['type' => 'text'],
            'is_all_limit'             => ['type' => 'long'],
            'is_all_limit_num'         => ['type' => 'long'],
            'is_customerday_limit'     => ['type' => 'long'],
            'is_order_limit'           => ['type' => 'long'],
            'order_limit_num'          => ['type' => 'long'],
            'agree_num'                => ['type' => 'long'],
            'expiration_date'          => ['type' => 'keyword'],
            'recommend_cook_desc'      => ['type' => 'text'],
            'is_recommend_cook'        => ['type' => 'long'],
            'is_weight'                => ['type' => 'long'],
            'member_price_json'        => ['type' => 'text']
        ],
        "member_index" => [
            'id'                   => ['type'=>'long'],
            'admin_id'             => ['type'=>'long'],
            'employee_id'          => ['type'=>'long'],
            'lewaimai_customer_id' => ['type'=>'long'],
            'level'                => ['type'=>'long'],
            'num'                  => ['type'=>'keyword'],
            'card_identify'        => ['type'=>'keyword'],
            'balance'              => ['type'=>'double'],
            'point'                => ['type'=>'long'],
            'name'                 => ['type'=>'text'],
            'sex'                  => ['type'=>'keyword'],
            'birthday'             => ['type'=>'date'],
            'tel'                  => ['type'=>'keyword'],
            'address'              => ['type'=>'text'],
            'freeze'               => ['type'=>'long'],
            'is_card'              => ['type'=>'long'],
            'open_no_card_payment' => ['type'=>'long'],
            'is_weixin'            => ['type'=>'long'],
            'total_recharge'       => ['type'=>'double'],
            'total_cost'           => ['type'=>'double'],
            'init_date'            => ['type'=>'date'],
            'is_get_membercard'    => ['type'=>'long'],
            'x_discount_value'     => ['type'=>'double'],
            'user_card_code'       => ['type'=>'keyword'],
            'is_verify_phone'      => ['type'=>'long'],
            'is_no_card'           => ['type'=>'long'],
            'member_level'         => ['type'=>'long'],
            'recharge_balance'     => ['type'=>'double'],
            'gift_balance'         => ['type'=>'double'],
            'nickname'             => ['type'=>'text']
        ],
    ]
];
