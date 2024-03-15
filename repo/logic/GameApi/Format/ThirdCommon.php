<?php
/**
 * User: nk
 * Date: 2018-12-29
 * Time: 11:32
 * Des : 第三公共结构
 */

namespace Logic\GameApi\Format;


class ThirdCommon {

    /**
     * 返回json 数据 完整包体是
     * [
     *   {
     *     'title':'标题',
     *     'type': 1,    //显示数据类型
     *     'image': 'url'   // type==1 的时候出现
     *     'content': '内容' // type==2 的时候出现
     *     'list': []       // type==3 的时候出现   ->  图片列表 or 后期扩展字段
     *   }
     * ]
     */
    // 龙虎开奖点
    const LH_LOTTERY_DOT = [
        '01' => '龙',
        '02' => '虎',
        '03' => '和',
        '04' => '龙-黑桃',
        '05' => '龙-红桃',
        '06' => '龙-梅花',
        '07' => '龙-方块',
        '08' => '虎-黑桃',
        '09' => '虎-红桃',
        '10' => '虎-梅花',
        '11' => '虎-方块',
        '12' => '押庄赢',
        '13' => '押庄输',
    ];
    // 龙虎开奖点
    const PG_LOTTERY = [
        '12' => '丁三',
        '24' => '二四',
        '23' => '杂五',
        '14' => '杂五',
        '25' => '杂七',
        '34' => '杂七',
        '26' => '杂八',
        '35' => '杂八',
        '36' => '杂九',
        '45' => '杂九',
        '15' => '零零六',
        '16' => '高脚七',
        '46' => '红头十',
        '56' => '斧头',
        '22' => '板凳',
        '33' => '长三',
        '55' => '梅牌',
        '13' => '鹅牌',
        '44' => '人牌',
        '11' => '地牌',
        '66' => '天牌',
    ];
    // 13水 牌形
    const LOTTERY_13S = [
        '0' => '乌龙',
        '1' => '一对',
        '2' => '两对',
        '3' => '三条',
        '4' => '顺子',
        '5' => '同花',
        '6' => '葫芦',
        '7' => '铁支',
        '8' => '同花顺',
        '14' => '三同花',
        '15' => '三顺子',
        '16' => '六对半',
        '17' => '五对三条',
        '18' => '四套三条',
        '19' => '凑一色',
        '20' => '全小',
        '21' => '全大',
        '22' => '三分天下',
        '23' => '三同花顺',
        '24' => '十二皇族',
        '25' => '十三水',
        '26' => '至尊青龙',
    ];
    // 百家乐 下注点
    const BJL_LOTTERY_DOT = [
        '2' => '庄',
        '1' => '闲',
        '4' => '上庄赢',
        '5' => '上庄输',
        '3' => '和',
        '6' => '庄对',
        '7' => '闲对',
        '8' => '大',
        '9' => '小',
    ];
    // 森林舞会 - 事件
    const SEN_LIN_WU_HUI_SHI_EVENT = [
        '1' => '无事件',
        '2' => '大三元',
        '3' => '大四喜',
        '4' => '霹雳闪电',
        '5' => '送灯'
    ];
    const SEN_LIN_WU_HUI_COLOR = [
        'R' => '红色',
        'G' => '绿色',
        'Y' => '黄色',
    ];
    const SEN_LIN_WU_HUI_ANIMAL = [
        'A' => '狮子',
        'B' => '熊猫',
        'C' => '猴子',
        'D' => '兔子',
    ];
    // 扑克图片的起始
    const POKER_IMAGE_INDEX_POS = [
        // '方块' => '♦' 0 - 13
        '0' => 0,
        // '梅花' => '♣' 17 - 29
        '1' => 16,
        // '红桃' => '♥' 33 - 45
        '2' => 32,
        // '黑桃' => '♠' 49 - 61
        '3' => 48,
        // '小王' => '▲' '大王' => '★' 62 63
        '4' => 61
    ];
    // JDB
    const JDB_GAME_TYPE_LIST = [
        0  => '经典游戏',
        7  => '捕鱼游戏',
        9  => '街机游戏',
        12 => '电子游戏',
        18 => '棋牌游戏'
    ];
    // JDB 捕鱼
    const JDB_GAME_BY_ROOM_TYPES = [
        0 => '欢乐区',
        1 => '富豪区',
        2 => '财神区'
    ];

    /**
     * 获取骰子图片
     */
    public static function getDiceImage($dice) {
        global $app;
        $cp_domain_url = $app->getContainer()->get('settings')['upload']['dsn']['qiniu']['public_domain'] . "/lottery/dice%s.png";
        return sprintf($cp_domain_url, $dice);
    }

    /**
     * 获取poker图片
     */
    public static function getPokerImage($poker_index) {
        global $app;
        $cp_domain_url = $app->getContainer()->get('settings')['upload']['dsn']['qiniu']['public_domain'] . "/poker/%d.png?imageView2/2/w/100";
        return sprintf($cp_domain_url, $poker_index);
    }
}