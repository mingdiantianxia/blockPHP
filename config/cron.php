<?php
/**
 * 定时任务
 */
return [
    "pid" => FKY_PROJECT_PATH .'/runtime/crond.pid',
    //定时任务
    'jobs' => [
//        [
//            'id' => 'test_job1',
//            'title' => '测试任务',
//             //定时配置，相对于linux的crontab, 系统支持精确到秒，第一位就是秒的配置，格式跟系统的crontab配置一样
//            'cron' => '* * * * * *',
//            'command' => 'system version',
//        ],

        [
            'id' => 'refresh_lewaimai_access_token',
            'title' => '定时刷新乐外卖access_token',
            'cron' => '0 */10 * * * *',
            'command' => 'accesstoken refreshlewaimai',
        ],
        [
            'id' => 'refresh_lekuaisong_access_token',
            'title' => '定时刷新乐快送access_token',
            'cron' => '0 */10 * * * *',
            'command' => 'accesstoken refreshlekuaisong',
        ],
//        [
//            'id' => 'check_timeout_order_call_merchant',
//            'title' => '检查外卖订单商家5分钟不接单，语音提醒商家',
//            'cron' => '0 */10 * * * *',
//            'command' => 'order callmerchant',
//        ],
		[
            'id' => 'withdraw_checkWithdrawStatus',
            'title' => '提现任务',
             //定时配置，相对于linux的crontab, 系统支持精确到秒，第一位就是秒的配置，格式跟系统的crontab配置一样
            'cron' => '0 0 */1 * * *',
            'command' => 'system version',
        ],

        [
            'id' => 'check_open_order_pay_status',
            'title' => '查询未支付订单的支付状态',
            'cron' => '0 * * * * *',
            'command' => 'order checkopenorderpaystatus',
        ],

        [
            'id' => 'check_refund_order_pay_status',
            'title' => '查询退款订单的退款状态',
            'cron' => '0 0 */1 * * *',
            'command' => 'order checkrefundorderpaystatus',
        ],

        [
            'id' => 'check_waimai_pay_cancel_status',
            'title' => '查询外卖订单未支付的状态',
            'cron' => '0 */1 * * * *',
            'command' => 'order checkwaimaipaycancelstatus',
        ],

        [
            'id' => 'check_tangshi_pay_cancel_status',
            'title' => '查询堂食订单未支付的状态',
            'cron' => '10 */1 * * * *',
            'command' => 'order checktangshipaycancelstatus',
        ],

        [
            'id' => 'check_tuangou_pay_cancel_status',
            'title' => '查询团购订单未支付的状态',
            'cron' => '20 */1 * * * *',
            'command' => 'order checktuangoupaycancelstatus',
        ],

        [
            'id' => 'trade_order_waimai_handThridOrder',
            'title' => '根据第三方外卖订单生成智铺子外卖订单',
            'cron' => '*/5 * * * * *',
            'command' => 'crontab createWaimaiOrderByThird',
        ],

        [
            'id' => 'clean_trash',
            'title' => '定时删除回收站90天以上数据',
            'cron' => '0 0 4 * * 6',
            'command' => 'trash cleantrash',
        ],

        [
            'id' => 'check_shouyinji_order_pay_status',
            'title' => '查询收银机未支付订单的支付状态',
            'cron' => '0 */1 * * * *',
            'command' => 'order checkShouyinjiOrderPayStatus',
        ],

        [
            'id' => 'check_shouyinji_order_refund_status',
            'title' => '查询收银机订单退款的状态',
            'cron' => '0 0 */1 * * *',
            'command' => 'order checkShouyinjiOrderRefundStatus',
        ],

        [
            'id' => 'check_mini_audit_status',
            'title' => '检测小程序的审核状态',
            'cron' => '0 0 */1 * * *',
            'command' => 'mini queryAuditStatus',
        ],
        [
            'id' => 'check_food_pay_time',
            'title' => '商品数量充值模块，每天早上0点，把商品数量充值过期的设置为免费版',
            'cron' => '0 0 0 */1 * *',
            'command' => 'crontab checkfoodpay',
        ],

        [
            'id' => 'check_level',
            'title' => '每天早上9点，对智铺子会员和收银机会员的过期时间轮询，过期的改为基础版。',
            'cron' => '0 0 9 * * *',
            'command' => 'crontab checkLevel',
        ],

        [
            'id' => 'autostock',
            'title' => '处理自动库存的问题，每天早上5点把所有开启了自动库存的商品重置为商家设置的库存数量',
            'cron' => '0 0 5 * * *',
            'command' => 'crontab autostock',
        ],
        [
            'id' => 'checktuangou',
            'title' => '每天早上0点处理团购券过期的情况',
            'cron' => '0 0 0 * * *',
            'command' => 'crontab checkTuangou',
        ],

        [
            'id' => 'queryOrderOpenStatus',
            'title' => '每分钟检查外卖订单商家5分钟不接单，语音提醒商家',
            'cron' => '0 */1 * * * *',
            'command' => 'crontab queryOrderOpenStatus',
        ],
        [
            'id' => 'agent_fencheng',
            'title' => '定时给代理商加分成',
            'cron' => '0 0 4 * * *',
            'command' => 'profit addAgentFenchengByT2',
        ],
        [
            'id' => 'agent_fenrun',
            'title' => '定时给智铺子算分润',
            'cron' => '0 0 4 * * *',
            'command' => 'profit addFenrunByT2',
        ],
        [
            'id' => 'deleteOldOrderList',
            'title' => '删除3天前的美团或饿了么订单推送记录',
            'cron' => '0 0 1 * * *',
            'command' => 'order deleteThirdOrderRecord',
        ],

//        [
//            'id' => 'check_dakuan_paying',
//            'title' => '查询天下支付打款状态',
//            'cron' => '0 0 */1 * * *',
//            'command' => 'crontab checkdakuanpaying',
//        ],
//
//        [
//            'id' => 'set_dakuan_paying',
//            'title' => '处理天下支付打款状态',
//            'cron' => '0 0 */1 * * *',
//            'command' => 'crontab setdakuanpaying',
//        ],
//
//        [
//            'id' => 'check_tianxia_status',
//            'title' => '查询天下进件状态',
//            'cron' => '0 0 */1 * * *',
//            'command' => 'tianxiareg checkstatus',
//        ],

        [
            'id' => 'check_leshua_status',
            'title' => '查询乐刷进件状态',
            'cron' => '0 0 */1 * * *',
            'command' => 'leshuareg checkstatus',
        ],

        [
            'id' => 'refresh_eleme_token',
            'title' => '定时刷新饿了么开放平台即将过期的token',
            'cron' => '0 0 */1 * * *',
            'command' => 'crontab refreshElemeToken',
        ],

        [
            'id' => 'send_sms_for_shop',
            'title' => '每天早上9点，对智铺子店铺微信功能检查过期日期，剩七天和一天发送短信提醒。',
            'cron' => '0 0 9 * * *',
            'command' => 'Shop ShopWxOverDueNotice',
        ],

        [
            'id' => 'member_consume_statistics',
            'title' => '每天早上0点10分，把今天的会员消费统计同步到昨天的字段去。',
            'cron' => '0 10 0 * * *',
            'command' => 'crontab MemberConsumeStatistice',
        ],


        [
            'id' => 'check_kanjia_pay_cancel_status',
            'title' => '查询砍价订单未支付的状态',
            'cron' => '30 */1 * * * *',
            'command' => 'order checkkanjiapaycancelstatus',
        ],

        [
            /* @author fukaiyao */
            'id' => 'check_seller_service_notify',
            'title' => '每天早上8点，检测商家的微信功能、小程序、商品数量、微页面是否为3天到期，插入消息',
            'cron' => '0 0 8 * * *',
            'command' => 'notify sellernotify',
        ],

        [
            /* @author fukaiyao 2018-7-11 23:24:05*/
            'id' => 'check_pintuan_order_status',
            'title' => '查询拼团订单超时的情况',
            'cron' => '0 */1 * * * *',
            'command' => 'order checkpintuanorderstatus',
        ],

        [
            /* @author fukaiyao 2018-7-11 23:24:05*/
            'id' => 'check_refund_pintuan_order_status',
            'title' => '查询拼团失败订单申请退款的情况',
            'cron' => '0 */1 * * * *',
            'command' => 'order checkrefundpintuanorderstatus',
        ],

        [
            /* @author fukaiyao 2018-8-14 17:52:46*/
            'id' => 'check_shangpinquan_pay_cancel_status',
            'title' => '查询商品券订单未支付的状态',
            'cron' => '0 */1 * * * *',
            'command' => 'order checkshangpinquanpaycancelstatus',
        ],

        [
            /* @author fukaiyao 2018-10-8 15:58:55*/
            'id' => 'check_lipincard_pay_cancel_status',
            'title' => '查询礼品卡订单未支付的状态',
            'cron' => '0 */1 * * * *',
            'command' => 'order checklipincardpaycancelstatus',
        ],

        [
            'id' => 'AgentDispatch',
            'title' => '每10分钟检查消息队列里是否有未处理成功的任务',
            'cron' => '0 */10 * * * *',
            'command' => 'agent AgentDispatch',
        ],

        [
            'id' => 'AgentCutPreBlance',
            'title' => '每10秒处理一次代理商后台开通商家后台功能扣减代理商预存款',
            'cron' => '*/10 * * * * *',
            'command' => 'agent AgentCutPreBlance',
        ],
        [
            'id' => 'memberBirthdayMsgcalling',
            'title' => '每天早上8点，检测会员生日营销，发生生日短信',
            'cron' => '0 0 8 * * *',
            'command' => 'memberBirthday Msgcalling',
        ],
        [
            'id' => 'memberBirthdayCouponsSent',
            'title' => '每天早上8点，检测会员生日营销，发放优惠券，商品券',
            'cron' => '0 0 8 * * *',
            'command' => 'memberBirthday Sendcoupons',
        ],
        [
            /* @author fukaiyao 2018-10-30 17:52:13*/
            'id' => 'check_pintuan_pay_cancel_status',
            'title' => '查询拼团订单未支付的状态',
            'cron' => '0 */1 * * * *',
            'command' => 'order checkpintuanpaycancelstatus',
        ],

        [
            /* @author fukaiyao 2018-11-1 16:29:21*/
            'id' => 'check_pintuan_refund_order_pay_status',
            'title' => '检测拼团退款失败订单状态直到退款成功',
            'cron' => '0 0 */1 * * *',
            'command' => 'order checkpintuanrefundorderpaystatus',
        ],

        [
            /* @author fukaiyao 2018-11-6 11:44:50*/
            'id' => 'check_pintuan_refund_overdue_order_pay_status',
            'title' => '每天早上4点，检测拼团超出退款期限的未成功退款订单',
            'cron' => '0 0 4 * * *',
            'command' => 'order checkpintuanrefundoverdueorderpaystatus',
        ],

        [
            'id' => 'cron_push_benchmark',
            'title' => '每分钟上报一次性能监控数据',
            'cron' => '0 */1 * * * *',
            'command' => 'statis push',
        ],

        [
            'id' => 'cron_booking_in_advance_notify',
            'title' => '每6分钟检查一次预订订单，发送预订就餐时间前发送消息提醒就餐',
            'cron' => '0 */6 * * * *',
            'command' => 'booking checkorderinadvancenotify',
        ],

        [
            'id' => 'closeMerchantJxc',
            'title' => '每小时检查一次商户版本等级，若已过期，则关闭进销存功能',
            'cron' => '0 0 */1 * * *',
            'command' => 'MerchantParams LevelOverDue',
        ],

        [
            'id' => 'cron_outtime_waimaiorder_tokitchen_in_advance_notify',
            'title' => '每分钟检查一次预订的外卖自提订单，发送自提订单信息到收银机',
            'cron' => '0 */1 * * * *',
            'command' => 'order checkouttimeorderinadvancenotify',
        ],
//        [
//            /* @author caoqijun 2019年4月3日18:22:45*/
//            'id' => 'cron_outtime_waimaiorder_retry_fastservice_in_advance_notify',
//            'title' => '每分钟检查一次重新推送订单到快服务，最多发送5次',
//            'cron' => '0 */1 * * * *',
//            'command' => 'order RetrySendToFasr',
//        ],
        [
            /* @author caoqijun 2019年1月15日20:09:50*/
            'id' => 'cron_outtime_waimaiorder_fastservice_in_advance_notify',
            'title' => '查询预约单推送到快服务',
            'cron' => '0 */5 * * * *',
            'command' => 'order SendToFast',
        ],
        [
            /* @author caoqijun 2019年1月15日20:09:50*/
            'id' => 'cron_outtime_waimaiorder_dadaservice_in_advance_notify',
            'title' => '查询预约单推送到达达',
            'cron' => '30 */5 * * * *',
            'command' => 'order SendToDada',
        ],

        [
            'id' => 'send_sms_for_wx_app',
            'title' => '每天早上9点，对每个小程序检查过期日期，剩七天和一天发送短信提醒。',
            'cron' => '0 0 9 * * *',
            'command' => 'Shop WxAppOverDueNotice',
        ],

//        [
//            'id' => 'send_waimaiorder_per_cancel_send',
//            'title' => '每分钟检查一次预约单是否到时还没有配送员接单，则将推送的订单取消',
//            'cron' => '0 */1 * * * *',
//            'command' => 'Order CancelSend',
//        ],

    ]
];