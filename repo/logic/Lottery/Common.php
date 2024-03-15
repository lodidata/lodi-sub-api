<?php

namespace Logic\Lottery;

use Overtrue\ChineseCalendar\Calendar;

/**
 * Class Common
 * @package Logic\Lottery
 * 彩种公用方法类
 */
class Common {

    //对应彩种放哪个通道进程中进行派彩
    public static $settlePrize = [
        'lotterysettle_send_1' => [
            110,    //泰国十五分彩  15分钟一期
        ],
        'lotterysettle_send_2' => [
            112,    //泰国五分彩  5分钟一期
        ],
        'lotterysettle_send_3' => [
            113,    //泰国三分彩  3分钟一期
        ],
        'lotterysettle_send_4' => [
            111,    //泰国十分彩  10分钟一期
        ],
    ];

    // 开奖优化，
    public static function sendQueOpenPrize($lottery_id,$lottery_number,$period_code) {
        $exchange = '';
        foreach (self::$settlePrize as $key=>$val) {
            if(in_array($lottery_id,$val)) {
                $exchange = $key;
            }
        }
        $exchange = $exchange ? $exchange : 'lotterysettle_send_1';
        \Utils\MQServer::send($exchange, [
            'lottery_number' => $lottery_number,
            'lottery_type' => $lottery_id,
            'period_code' => $period_code
        ]);
    }

    /**
     * 根据号码获取生肖
     *
     * @param $num
     * @param $year
     *
     * @return mixed
     */
    public static function getSx($num, $year = false) {
        if ($year === false) {
            $date = explode('-', date('Y-m-d'));

            //获取生效需要使用农历年计算
            $calendar = new Calendar();
            $result = $calendar->solar($date[0], $date[1], $date[2]);

            $year = $result['lunar_year'];
        }

        $shengxiao = ['猴', '鸡', '狗', '猪', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊'];
        $sx_year = ($year - $num + 1) % 12;

        return $shengxiao[$sx_year];
    }
}