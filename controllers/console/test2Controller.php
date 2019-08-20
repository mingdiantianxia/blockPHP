<?php

namespace controllers\console;

use controllers\BaseController;
use fky\classs\Config;
use fky\classs\Db;
use fky\classs\LoadFactory;
use fky\classs\Phpredis;

/**
 * 定时任务测试
 */
class Test2Controller extends BaseController
{

    public function test()
    {
        $day = date('d');
        $week = date('w');
        $hour = date('H');
        $min = date('i');

        if ($week == 0) {
            echo '周日休息！' . "\n";
            return true;
        }

        if ($hour < 9 || ($hour == 9 && $min < 30) || ($hour == 12 && $min > 0) || $hour == 13 || $hour > 21 || ($hour == 21 && $min > 0)) {
            echo '非工作时间！' . "\n";
            return true;
        }

        $num = 6 - $week;
        //0小周，1大周（周六不上班）
        $isBigWeek = (strtotime(date('Y-m-d').' 00:00:00 +'.$num.' day') - strtotime('2019-07-13 00:00:00'))/604800%2;
        if ($isBigWeek && $week == 6) {
            echo '本周双休！' . "\n";
            return true;
        }

        //周五周六超过19点不提醒
        if (in_array($week, [5, 6]) && $hour > 19) {
            echo '非工作时间！' . "\n";
            return true;
        }

//        $is_end_day = false;//本周最后一天
//        if ($isBigWeek && $week == 5) {
//            $is_end_day = true;
//        }
//        elseif (!$isBigWeek && $week == 6) {
//            $is_end_day = true;
//        }

        $tip = '温馨提示';
        $to_list = ["@all"];
        $to_mobile_list = ["@all"];
        $is_send_text = true;

        $tip2 = '';
        $is_send_markdown = true;

        switch ($hour) {
            case 9:
                $is_send_text = false;
                $is_send_markdown = false;
                $tip2 = '占个位';
                if ($min == 30) {
                    $is_send_text = true;
                    $to_mobile_list = false;
                    $tip = '9:30上班时间到了，哈哈，有人贡献下午茶吗？';

                    if ($day == 10 && !in_array($week, [0, 6])) {
                        $tip = '今天10号要发工资咯，大家晚上又可以加鸡腿啦！';
                    }
                    elseif ($isBigWeek && $week == 1) {
                        $tip = '大周，这周双休哦！';
                    }
                    elseif ($isBigWeek && $week == 5) {
                        $tip = '大周，周末双休哦，小伙伴们加油，早点下班！';
                    }
                    elseif (!$isBigWeek && $week == 1) {
                        $tip = '小周，这周要上六天班！';
                    }
                    elseif (!$isBigWeek && $week == 6) {
                        $tip = '小周，明天就可以休息啦，小伙伴们加油，早点下班！';
                    }
                }
                break;
            case 11:
                $tip2 = '11点了，点外卖的时间到了，不然12点要饿肚子咯！';
                $is_send_text = false;
                break;
            case 12:
                $tip2 = '午饭时间到！';
                $is_send_text = false;
                break;
            case 18:
                if (in_array($week, [5, 6]) && $min == 30) {
                    $tip2 = '离下班时间还有半个小时，抓紧时间工作哦！';
                }
                elseif (in_array($week, [5, 6])) {
                    $tip2 = '今晚不用加班，离下班还有1个小时！';
                }elseif ($min == 30) {
                    $tip2 = '晚饭时间到！';
                } else {
                    $tip2 = '离晚饭时间还有半个小时，请继续坚持工作！';
                }

                $is_send_text = false;
                break;
            case 19:
                if (in_array($week, [5, 6])) {
                    $tip2 = '下班时间到！';
                } else {
                    return true;
                }
                break;
            case 21:
                $tip2 = '9点到，准备下班回家喝汤咯！';
                break;
            default:
                $to_mobile_list = ['15521311931'];
                break;
        }

        if (empty($tip2)) {
            $is_send_text = false;
            $is_send_markdown = false;
        }

        //发送text格式消息
        $content = array(
            'msgtype' => "text",
            "text" => [
                "content" => $tip,
//              "mentioned_list" => $to_list,
                "mentioned_mobile_list" => $to_mobile_list
            ]
        );
        if ($is_send_text) {
            $content_1 =  urldecode(json_encode($this->returnParams($content)));
            $result = loadc('HttpRequest')->POST(
                'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=f875ede7-b127-49a6-8a9a-a729132572dc',
//                'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=1e33cf29-2dfe-40ee-8b8b-c9adaaf3ecad',
                $content_1,
                array(CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=UTF-8'])
            );
        }

        //发送markdown格式消息
        $content2 = array(
            'msgtype' => "markdown",
            "markdown" => [
                "content" => "饭点提醒：<font color=\"info\">" . $tip2 . "</font>\n>当前时间:<font color=\"comment\">" . date('H:i') . "</font>",
            ]
        );
        if ($is_send_markdown) {
            $content_2 = json_encode($content2);
            $result = loadc('HttpRequest')->POST(
                'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=f875ede7-b127-49a6-8a9a-a729132572dc',
//                'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=1e33cf29-2dfe-40ee-8b8b-c9adaaf3ecad',
                $content_2,
                array(CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=UTF-8'])
            );
        }

        echo '饭点定时任务成功' . date('Y-m-d H:i:s') . "\n";
        return false;//返回false普通日志将收集错误日志
    }


    //个人提醒
    public function test2()
    {
        $day = date('d');
        $week = date('w');
        $hour = date('H');
        $min = date('i');

        if ($week == 0) {
            echo '周日休息！' . "\n";
            return true;
        }

        if ($hour < 9 || ($hour == 9 && $min < 30) || ($hour == 12 && $min > 0) || $hour == 13 || $hour > 21 || ($hour == 21 && $min > 0)) {
            echo '非工作时间！' . "\n";
            return true;
        }

        $num = 6 - $week;
        //0小周，1大周（周六不上班）
        $isBigWeek = (strtotime(date('Y-m-d').' 00:00:00 +'.$num.' day') - strtotime('2019-07-13 00:00:00'))/604800%2;
        if ($isBigWeek && $week == 6) {
            echo '大周休息！' . "\n";
            return true;
        }

        //周五周六超过19点不提醒
        if (in_array($week, [5, 6]) && ($hour > 19 || ($hour == 19 && $min > 0))) {
            echo '非工作时间！' . "\n";
            return true;
        }

//        $is_end_day = false;//本周最后一天
//        if ($isBigWeek && $week == 5) {
//            $is_end_day = true;
//        }
//        elseif (!$isBigWeek && $week == 6) {
//            $is_end_day = true;
//        }

        $tips_arr = array(
            0 => '起来活动一下吧！',
            1 => '该喝口水了！',
            2 => '抓紧时间干活吧，不然晚上没时间溜达咯！',
            3 => '吃饭时间到！',
            4 => '下班时间到！',
            5 => '抓紧时间干活，晚上早点下班！',
            6 => '林宇澄该起来走动啦！',
            7 => '离下班还有半小时！',
        );

        $to_list = ["@all"];
        $to_mobile_list = ["13232720275"];
        $is_send_text = true;

        //提醒的时间列表
        $min_list = array('00', '30', '50');

        //根据时间交换提醒语
        $tips = $tips_arr[0];
        if (in_array($min, $min_list)) {
            switch ($min) {
                case 0:
                    if ($hour == 12) {
                        $tips = $tips_arr[3];
                    }

                    if ($hour == 15 && in_array($week, [5, 6])) {
                        $tips = $tips_arr[5];
                    } elseif ($hour == 15) {
                        $tips = $tips_arr[2];
                    }

                    if ($hour == 19 && in_array($week, [5, 6])) {
                        $tips = $tips_arr[4];
                    }

                    if ($hour == 21) {
                        $tips = $tips_arr[4];
                    }

                    break;
                case 30:
                    $tips = $tips_arr[1];

                    if ($hour == 9) {
                        $restDays = (strtotime('2019-09-27 00:00:00') - strtotime(date('Y-m-d').' 00:00:00'))/86400;
                        $tips = '今天'.date('Y-m-d').'，离申请离职还有'.$restDays.'天！';
                    }

                    if ($hour == 18 && in_array($week, [5, 6])) {
                        $tips = $tips_arr[7];
                    } elseif ($hour == 18) {
                        $tips = $tips_arr[3];
                    }
                    break;
                default:
                    $tips = $tips_arr[6];
                    $to_mobile_list = ['15521311931'];
                    break;
            }

        } else {
            echo '不在提醒时间范围内！' . "\n";
            return true;
        }

        //发送text格式消息
        $content = array(
            'msgtype' => "text",
            "text" => [
                "content" => $tips,
//              "mentioned_list" => $to_list,
                "mentioned_mobile_list" => $to_mobile_list
            ]
        );
        if ($is_send_text) {
            $content_1 = urldecode(json_encode($this->returnParams($content)));
            $result = loadc('HttpRequest')->POST(
                'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=1e33cf29-2dfe-40ee-8b8b-c9adaaf3ecad',
                $content_1,
                array(CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=UTF-8'])
            );
        }

        echo '定时任务成功' . date('Y-m-d H:i:s') . "\n";
        return true;
    }

    public function test3()
    {
//        $tasks = Phpredis::getInstance()->delete('fky_dbevent_fullsync_task');
//        $key_arr = Phpredis::getInstance()->keys('*');
//        Phpredis::getInstance()->delete($key_arr);
//        var_dump($tasks);die;

//        $paramArray = $this->GPC();

        $tableName = 'wx_access_token';
        $dbInstance = LoadFactory::lc('db', Config::getInstance()->get('db', 'config'));
        $sql = "select * from {$tableName} limit 500";
        $datas = $dbInstance->pdo->query($sql, \PDO::FETCH_ASSOC)->fetchAll();
//
        $dbInstance2 = Db::getInstance('slaveDb');
        $result = $dbInstance2->insert($tableName, $datas);
        var_export($result);die;

//        $paramArray = array(
//            'table' => 'wx_access_token',
//            'listenerid' => 'test',
//            'pk' => 'admin_id',
//            'offset' => 1,
//            'maxOffset' => 500,
//        );
//        $queryStr = http_build_query($paramArray);
//        $data = loadc('HttpRequest')->GET('http://127.0.0.1:8181?'.$queryStr);
//        var_dump($data);
        echo 'good';
        return true;
    }
}
