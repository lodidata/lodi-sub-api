<?php
//1 - 150
return [
    // 全局(每个具体玩法里面定义后会覆盖)
    0 => [
        'periodCodeFormat' => ['exec' => 'SSCFormat::periodCodeFormat'],

        'format' => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 5]],

        'valid' => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
    ],
    // 五星 直选复式
    1 => [
        'name'             => ['标准', '五星直选', '直选复式'],
        'result'           => ['exec' => 'SSCResult::result5Zxfs'],
        'standardOdds'     => [100000],
        'standardOddsDesc' => ['直选复式'],
        'pretty'           => ['exec' => 'SSCPretty::prettyDwd'],
    ],

    // 五星 直选组合
    2 => [
        'name'             => ['标准', '五星直选', '直选组合'],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 5]],
        'result'           => ['exec' => 'SSCResult::result5Zxzh'],
        'standardOdds'     => [100000, 10000, 1000, 100, 10],
        'standardOddsDesc' => ['五星', '四星', '三星', '二星', '一星'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    // 五星 通选复式
    3 => [
        'name'             => ['标准', '五星直选', '通选复式'],
        'result'           => ['exec' => 'SSCResult::result5Txfs'],
        'desc'             => [
            '从五个位置各选1个或多个号码.开奖5次.中五位全对奖金N1元/注,中后三或前三N2元/注,中后二或前二N3元/注',
            '投注:12345,2元.开奖号码:12345,即可中五星；开奖号码12378或09345,即可中三星；开奖号码12678或09845,即可中二星；.',
        ],
        'standardOdds'     => [100000, 1000, 100],
        'standardOddsDesc' => ['五星', '三星', '二星'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    4 => [
        'name'             => ['标准', '五星组选', '组选120'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 5]],
        'result'           => ['exec' => 'SSCResult::result5Comb120'],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 5]],
        'standardOdds'     => [833],
        'standardOddsDesc' => ['组选120'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    5 => [
        'name'             => ['标准', '五星组选', '组选60'],
        'result'           => ['exec' => 'SSCResult::result5Comb60'],
        'format'           => ['exec' => 'SSCFormat::formatComb60'],
        'valid'            => ['exec' => 'SSCValid::validComb60'],
        'standardOdds'     => [1666],
        'standardOddsDesc' => ['组选60'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    6 => [
        'name'             => ['标准', '五星组选', '组选30'],
        'result'           => ['exec' => 'SSCResult::result5Comb30'],
        'valid'            => ['exec' => 'SSCValid::validComb30'],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 2, 'twoLength' => 1]],
        'standardOdds'     => [3333],
        'standardOddsDesc' => ['组选30'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    7 => [
        'name'             => ['标准', '五星组选', '组选20'],
        'result'           => ['exec' => 'SSCResult::result5Comb20'],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validComb20'],
        'standardOdds'     => [5000],
        'standardOddsDesc' => ['组选20'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    8 => [
        'name'             => ['标准', '五星组选', '组选10'],
        'result'           => ['exec' => 'SSCResult::result5Comb10'],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 1]],
        'valid'            => ['exec' => 'SSCValid::validComb10'],
        'standardOdds'     => [10000],
        'standardOddsDesc' => ['组选10'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    9 => [
        'name'             => ['标准', '五星组选', '组选5'],
        'result'           => ['exec' => 'SSCResult::result5Comb5'],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 1]],
        'valid'            => ['exec' => 'SSCValid::validComb10'],
        'standardOdds'     => [20000],
        'standardOddsDesc' => ['组选5'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    10 => [
        'name'             => ['标准', '五星特殊', '百家乐'],
        'result'           => ['exec' => 'SSCResult::result5Baccarat'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        // 'format' => ['exec' => 'SSCFormat::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'standardOdds'     => [2.1, 10, 100, 5.2, 2.1, 10, 100, 5.2],
        // 'standardOddsDesc' => ['百家乐'],
        // 'standardOddsDescAlias' => ['庄','庄对子','庄豹子','庄天王', '闲','闲对子','闲豹子','闲天王'],
        'standardOddsDesc' => ['庄', '庄对子', '庄豹子', '庄天王', '闲', '闲对子', '闲豹子', '闲天王'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    11 => [
        'name'             => ['标准', '五星特殊', '一帆风顺'],
        'result'           => ['exec' => 'SSCResult::result5Yffs'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::validYffs'],
        'standardOdds'     => [2.44],
        'standardOddsDesc' => ['一帆风顺'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    12 => [
        'name'             => ['标准', '五星特殊', '好事成双'],
        'result'           => ['exec' => 'SSCResult::result5Yffs', 'args' => ['count' => 2]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::validYffs'],
        'standardOdds'     => [12.28],
        'standardOddsDesc' => ['好事成双'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    13 => [
        'name'             => ['标准', '五星特殊', '三星报喜'],
        'result'           => ['exec' => 'SSCResult::result5Yffs', 'args' => ['count' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::validYffs'],
        'standardOdds'     => [116.82],
        'standardOddsDesc' => ['三星报喜'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    14 => [
        'name'             => ['标准', '五星特殊', '四季发财'],
        'result'           => ['exec' => 'SSCResult::result5Yffs', 'args' => ['count' => 4]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::validYffs'],
        'standardOdds'     => [2173.91],
        'standardOddsDesc' => ['四季发财'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    15 => [
        'name'             => ['标准', '四星直选', '后四复式'],
        'result'           => ['exec' => 'SSCResult::result4Hsfs'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 4]],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [10000],
        'standardOddsDesc' => ['后四复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    16 => [
        'name'             => ['标准', '四星直选', '后四组合'],
        'result'           => ['exec' => 'SSCResult::result4Hszh'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 4]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 4]],
        'standardOdds'     => [10000, 1000, 100, 10],
        'standardOddsDesc' => ['四星', '三星', '二星', '一星'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    17 => [
        'name'             => ['标准', '四星直选', '前四复式'],
        'result'           => ['exec' => 'SSCResult::result4Qsfs'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 4]],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [10000],
        'standardOddsDesc' => ['前四复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    18 => [
        'name'             => ['标准', '四星直选', '前四组合'],
        'result'           => ['exec' => 'SSCResult::result4Qszh'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 4]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 4]],
        'standardOdds'     => [10000, 1000, 100, 10],
        'standardOddsDesc' => ['四星', '三星', '二星', '一星'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    19 => [
        'name'             => ['标准', '四星组选', '后四组选24'],
        'result'           => ['exec' => 'SSCResult::result4Hszx24', 'args' => ['start' => 1, 'len' => 5]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 4]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 4]],
        'standardOdds'     => [416],
        'standardOddsDesc' => ['后四组选24'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    20 => [
        'name'             => ['标准', '四星组选', '后四组选12'],
        'result'           => ['exec' => 'SSCResult::result4Hszx12', 'args' => ['start' => 1, 'len' => 4]],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validComb20'],
        'standardOdds'     => [833],
        'standardOddsDesc' => ['后四组选12'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    22 => [
        'name'             => ['标准', '四星组选', '后四组选6'],
        'result'           => ['exec' => 'SSCResult::result4Hszx6', 'args' => ['start' => 1, 'len' => 5]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [1666],
        'standardOddsDesc' => ['后四组选6'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    23 => [
        'name'             => ['标准', '四星组选', '后四组选4'],
        'result'           => ['exec' => 'SSCResult::result4Hszx4'],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 1]],
        'valid'            => ['exec' => 'SSCValid::validComb10'],
        'standardOdds'     => [2500],
        'standardOddsDesc' => ['后四组选4'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    24 => [
        'name'             => ['标准', '四星组选', '前四组选24'],
        'result'           => ['exec' => 'SSCResult::result4Hszx24', 'args' => ['start' => 0, 'len' => 4]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 4]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 4]],
        'standardOdds'     => [416],
        'standardOddsDesc' => ['前四组选24'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    25 => [
        'name'             => ['标准', '四星组选', '前四组选12'],
        'result'           => ['exec' => 'SSCResult::result4Hszx12', 'args' => ['start' => 0, 'len' => 4]],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validComb20'],
        'standardOdds'     => [833],
        'standardOddsDesc' => ['前四组选12'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    26 => [
        'name'             => ['标准', '四星组选', '前四组选6'],
        'result'           => ['exec' => 'SSCResult::result4Hszx6', 'args' => ['start' => 0, 'len' => 4]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [1666],
        'standardOddsDesc' => ['前四组选6'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    27 => [
        'name'             => ['标准', '四星组选', '前四组选4'],
        'result'           => ['exec' => 'SSCResult::result4Hszx4', 'args' => ['start' => 0, 'len' => 4]],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 1]],
        'valid'            => ['exec' => 'SSCValid::validComb10'],
        'standardOdds'     => [2500],
        'standardOddsDesc' => ['前四组选4'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    28 => [
        'name'             => ['标准', '后三', '直选复式'],
        'result'           => ['exec' => 'SSCResult::result3Zxfs'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 3]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000],
        'standardOddsDesc' => ['直选复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    29 => [
        'name'             => ['标准', '后三', '直选组合'],
        'result'           => ['exec' => 'SSCResult::result3Zxzh'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 3]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 3]],
        'standardOdds'     => [1000, 100, 10],
        'standardOddsDesc' => ['三星', '二星', '一星'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    31 => [
        'name'             => ['标准', '后三', '直选和值'],
        'result'           => ['exec' => 'SSCResult::result3Zxhz'],
        'format'           => ['exec' => 'SSCFormat::formatZxhz'],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000, 333.33, 166.67, 100, 66.67, 47.62, 35.71, 27.78, 22.22, 18.18, 15.87, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    32 => [
        'name'             => ['标准', '后三', '直选跨度'],
        'result'           => ['exec' => 'SSCResult::result3Zxkd', 'args' => ['start' => 2, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [100, 18.52, 10.42, 7.94, 6.94, 6.67, 6.94, 7.94, 10.42, 18.52],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    33 => [
        'name'             => ['标准', '后三', '组三包号'],
        'result'           => ['exec' => 'SSCResult::result3Zsbh', 'args' => ['start' => 2, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2, 'defaultBetCount' => 2]],
        'standardOdds'     => [166.67],
        'standardOddsDesc' => ['组三包号'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    34 => [
        'name'             => ['标准', '后三', '组三直选'],
        'result'           => ['exec' => 'SSCResult::result3Zszx', 'args' => ['start' => 2, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 1]],
        'valid'            => ['exec' => 'SSCValid::validComb10'],
        'standardOdds'     => [333.33],
        'standardOddsDesc' => ['组三直选'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    35 => [
        'name'             => ['标准', '后三', '组六包号'],
        'result'           => ['exec' => 'SSCResult::result3Zlbh', 'args' => ['start' => 2, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 3]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 3, 'defaultBetCount' => 1]],
        'standardOdds'     => [166.67],
        'standardOddsDesc' => ['组六包号'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    36 => [
        'name'             => ['标准', '后三', '后三豹子'],
        'result'           => ['exec' => 'SSCResult::result3Hsbz', 'args' => ['start' => 2, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 0]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000],
        'standardOddsDesc' => ['豹子通选', '000', '111', '222', '333', '444', '555', '666', '777', '888', '999'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    37 => [
        'name'             => ['标准', '后三', '后三顺子'],
        'result'           => ['exec' => 'SSCResult::result3Hsbz2', 'args' => ['start' => 2, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 1]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66],
        'standardOddsDesc' => ['顺子通选', '012', '123', '234', '345', '456', '567', '678', '789', '890', '901'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    38 => [
        'name'             => ['标准', '后三', '后三对子'],
        'result'           => ['exec' => 'SSCResult::result3Hsdz', 'args' => ['start' => 2, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 2]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37],
        'standardOddsDesc' => ['对子通选', '00', '11', '22', '33', '44', '55', '66', '77', '88', '99'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    39 => [
        'name'             => ['标准', '中三', '直选复式'],
        'result'           => ['exec' => 'SSCResult::result3Zxfs', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 3]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000],
        'standardOddsDesc' => ['直选复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    40 => [
        'name'             => ['标准', '中三', '直选组合'],
        'result'           => ['exec' => 'SSCResult::result3Zxzh', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 3]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 3]],
        'standardOdds'     => [1000, 100, 10],
        'standardOddsDesc' => ['三星', '二星', '一星'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    41 => [
        'name'             => ['标准', '中三', '直选和值'],
        'result'           => ['exec' => 'SSCResult::result3Zxhz', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::formatZxhz'],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000, 333.33, 166.67, 100, 66.67, 47.62, 35.71, 27.78, 22.22, 18.18, 15.87, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    42 => [
        'name'             => ['标准', '中三', '直选跨度'],
        'result'           => ['exec' => 'SSCResult::result3Zxkd', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [100, 18.52, 10.42, 7.94, 6.94, 6.67, 6.94, 7.94, 10.42, 18.52],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    43 => [
        'name'             => ['标准', '中三', '组三包号'],
        'result'           => ['exec' => 'SSCResult::result3Zsbh', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2, 'defaultBetCount' => 2]],
        'standardOdds'     => [166.67],
        'standardOddsDesc' => ['组三包号'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    44 => [
        'name'             => ['标准', '中三', '组三直选'],
        'result'           => ['exec' => 'SSCResult::result3Zszx', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 1]],
        'valid'            => ['exec' => 'SSCValid::validComb10'],
        'standardOdds'     => [333.33],
        'standardOddsDesc' => ['组三直选'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    45 => [
        'name'             => ['标准', '中三', '组六包号'],
        'result'           => ['exec' => 'SSCResult::result3Zlbh', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 3]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 3, 'defaultBetCount' => 1]],
        'standardOdds'     => [166.67],
        'standardOddsDesc' => ['组六包号'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    46 => [
        'name'             => ['标准', '中三', '中三豹子'],
        'result'           => ['exec' => 'SSCResult::result3Hsbz', 'args' => ['start' => 1, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 0]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000],
        'standardOddsDesc' => ['豹子通选', '000', '111', '222', '333', '444', '555', '666', '777', '888', '999'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    47 => [
        'name'             => ['标准', '中三', '中三顺子'],
        'result'           => ['exec' => 'SSCResult::result3Hsbz2', 'args' => ['start' => 1, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 1]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66],
        'standardOddsDesc' => ['顺子通选', '012', '123', '234', '345', '456', '567', '678', '789', '890', '901'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    48 => [
        'name'             => ['标准', '中三', '中三对子'],
        'result'           => ['exec' => 'SSCResult::result3Hsdz', 'args' => ['start' => 1, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 2]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37],
        'standardOddsDesc' => ['对子通选', '00', '11', '22', '33', '44', '55', '66', '77', '88', '99'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    49 => [
        'name'             => ['标准', '前三', '直选复式'],
        'result'           => ['exec' => 'SSCResult::result3Zxfs', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 3]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000],
        'standardOddsDesc' => ['直选复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    50 => [
        'name'             => ['标准', '前三', '直选组合'],
        'result'           => ['exec' => 'SSCResult::result3Zxzh', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 3]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 3]],
        'standardOdds'     => [1000, 100, 10],
        'standardOddsDesc' => ['三星', '二星', '一星'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    51 => [
        'name'             => ['标准', '前三', '直选和值'],
        'result'           => ['exec' => 'SSCResult::result3Zxhz', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::formatZxhz'],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000, 333.33, 166.67, 100, 66.67, 47.62, 35.71, 27.78, 22.22, 18.18, 15.87, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    52 => [
        'name'             => ['标准', '前三', '直选跨度'],
        'result'           => ['exec' => 'SSCResult::result3Zxkd', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [100, 18.52, 10.42, 7.94, 6.94, 6.67, 6.94, 7.94, 10.42, 18.52],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    53 => [
        'name'             => ['标准', '前三', '组三包号'],
        'result'           => ['exec' => 'SSCResult::result3Zsbh', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2, 'defaultBetCount' => 2]],
        'standardOdds'     => [166.67],
        'standardOddsDesc' => ['组三包号'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    54 => [
        'name'             => ['标准', '前三', '组三直选'],
        'result'           => ['exec' => 'SSCResult::result3Zszx', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::formatComb60', 'args' => ['oneLength' => 1, 'twoLength' => 1]],
        'valid'            => ['exec' => 'SSCValid::validComb10'],
        'standardOdds'     => [333.33],
        'standardOddsDesc' => ['组三直选'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    55 => [
        'name'             => ['标准', '前三', '组六包号'],
        'result'           => ['exec' => 'SSCResult::result3Zlbh', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 3]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 3, 'defaultBetCount' => 1]],
        'standardOdds'     => [166.67],
        'standardOddsDesc' => ['组六包号'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    56 => [
        'name'             => ['标准', '前三', '前三豹子'],
        'result'           => ['exec' => 'SSCResult::result3Hsbz', 'args' => ['start' => 0, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 0]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000],
        'standardOddsDesc' => ['豹子通选', '000', '111', '222', '333', '444', '555', '666', '777', '888', '999'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    57 => [
        'name'             => ['标准', '前三', '前三顺子'],
        'result'           => ['exec' => 'SSCResult::result3Hsbz2', 'args' => ['start' => 0, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 1]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66],
        'standardOddsDesc' => ['顺子通选', '012', '123', '234', '345', '456', '567', '678', '789', '890', '901'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    58 => [
        'name'             => ['标准', '前三', '前三对子'],
        'result'           => ['exec' => 'SSCResult::result3Hsdz', 'args' => ['start' => 0, 'len' => 3]],
        // 'format' => ['exec' => 'SSCFormat::format3bsd', 'args' => ['type' => 2]],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37],
        'standardOddsDesc' => ['对子通选', '00', '11', '22', '33', '44', '55', '66', '77', '88', '99'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    59 => [
        'name'             => ['标准', '后二', '直选复式'],
        'result'           => ['exec' => 'SSCResult::result2Zxfs', 'args' => ['start' => 3, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 2]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [100],
        'standardOddsDesc' => ['直选复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    60 => [
        'name'             => ['标准', '后二', '直选和值'],
        'result'           => ['exec' => 'SSCResult::result2Zxhz', 'args' => ['start' => 3, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::formatZxhz', 'args' => ['max' => 18]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000, 333.33, 166.67, 100, 66.67, 47.62, 35.71, 27.78, 22.22, 18.18, 15.87, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 15.87],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    61 => [
        'name'             => ['标准', '后二', '直选跨度'],
        'result'           => ['exec' => 'SSCResult::result2Zxkd', 'args' => ['start' => 3, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [10, 5.55, 6.25, 7.14, 8.33, 10, 12.5, 16.67, 25, 50],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    62 => [
        'name'             => ['标准', '后二', '组选复式'],
        'result'           => ['exec' => 'SSCResult::result2Zuxfs', 'args' => ['start' => 3, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [10],
        'standardOddsDesc' => ['组选复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    63 => [
        'name'             => ['标准', '前二', '直选复式'],
        'result'           => ['exec' => 'SSCResult::result2Zxfs', 'args' => ['start' => 0, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 2]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [100],
        'standardOddsDesc' => ['直选复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    64 => [
        'name'             => ['标准', '前二', '直选和值'],
        'result'           => ['exec' => 'SSCResult::result2Zxhz', 'args' => ['start' => 0, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::formatZxhz', 'args' => ['max' => 18]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [1000, 333.33, 166.67, 100, 66.67, 47.62, 35.71, 27.78, 22.22, 18.18, 15.87, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 15.87],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    65 => [
        'name'             => ['标准', '前二', '直选跨度'],
        'result'           => ['exec' => 'SSCResult::result2Zxkd', 'args' => ['start' => 0, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 1]],
        'standardOdds'     => [10, 5.55, 6.25, 7.14, 8.33, 10, 12.5, 16.67, 25, 50],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    66 => [
        'name'             => ['标准', '前二', '组选复式'],
        'result'           => ['exec' => 'SSCResult::result2Zuxfs', 'args' => ['start' => 0, 'len' => 2]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [10],
        'standardOddsDesc' => ['组选复式'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    67 => [
        'name'             => ['快捷', '两面', '龙虎斗'],
        'result'           => ['exec' => 'SSCResult::resultLMLhd'],
        'format'           => ['exec' => 'SSCFormat::formatByOddsDesc2'],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
            2.2, 2.2, 10,
        ],
        'standardOddsDesc' => [
            '万千龙', '万千虎', '万千合',
            '万百龙', '万百虎', '万百合',
            '万十龙', '万十虎', '万十合',
            '万个龙', '万个虎', '万个合',
            '千百龙', '千百虎', '千百合',
            '千十龙', '千十虎', '千十合',
            '千个龙', '千个虎', '千个合',
            '百十龙', '百十虎', '百十合',
            '百个龙', '百个虎', '百个合',
            '十个龙', '十个虎', '十个合',
        ],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    68 => [
        'name'                  => ['快捷', '两面', '五字和'],
        'result'                => ['exec' => 'SSCResult::resultLMWzh'],
        'format'                => ['exec' => 'SSCFormat::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid'                 => ['exec' => 'SSCValid::valid'],
        'standardOdds'          => [2],
        'standardOddsDesc'      => ['五字和'],
        'standardOddsDescAlias' => [
            '总数大', '总数小', '总数单', '总数双',
            '和尾数大', '和尾数小', '和尾数质', '和尾数合',
        ],
        'pretty'                => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    69 => [
        'name'             => ['快捷', '定位胆', '球号'],
        'result'           => ['exec' => 'SSCResult::resultDDwd'],
        'format'           => ['exec' => 'SSCFormat::formatDwd'],
        'valid'            => ['exec' => 'SSCValid::validDwd'],
        'standardOdds'     => [10],
        'standardOddsDesc' => ['球号'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    70 => [
        'name'             => ['快捷', '不定位', '后三一码'],
        'result'           => ['exec' => 'SSCResult::resultBHsym'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [3.69],
        'standardOddsDesc' => ['后三一码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    71 => [
        'name'             => ['快捷', '不定位', '中三一码'],
        'result'           => ['exec' => 'SSCResult::resultBHsym', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [3.69],
        'standardOddsDesc' => ['中三一码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    72 => [
        'name'             => ['快捷', '不定位', '前三一码'],
        'result'           => ['exec' => 'SSCResult::resultBHsym', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [3.69],
        'standardOddsDesc' => ['前三一码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    73 => [
        'name'             => ['快捷', '不定位', '后三二码'],
        'result'           => ['exec' => 'SSCResult::resultBHsem'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [18.51],
        'standardOddsDesc' => ['后三二码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    74 => [
        'name'             => ['快捷', '不定位', '中三二码'],
        'result'           => ['exec' => 'SSCResult::resultBHsem', 'args' => ['start' => 1, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [18.51],
        'standardOddsDesc' => ['中三二码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    75 => [
        'name'             => ['快捷', '不定位', '前三二码'],
        'result'           => ['exec' => 'SSCResult::resultBHsem', 'args' => ['start' => 0, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [18.51],
        'standardOddsDesc' => ['前三二码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    76 => [
        'name'             => ['快捷', '不定位', '后四一码'],
        'result'           => ['exec' => 'SSCResult::resultBHsym', 'args' => ['start' => 1, 'len' => 4]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [2.9],
        'standardOddsDesc' => ['后四一码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    77 => [
        'name'             => ['快捷', '不定位', '后四二码'],
        'result'           => ['exec' => 'SSCResult::resultBHsem', 'args' => ['start' => 1, 'len' => 4]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [10.2],
        'standardOddsDesc' => ['后四二码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    78 => [
        'name'             => ['快捷', '不定位', '五星二码'],
        'result'           => ['exec' => 'SSCResult::resultBHsem', 'args' => ['start' => 0, 'len' => 5]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 2]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 2]],
        'standardOdds'     => [6.81],
        'standardOddsDesc' => ['五星二码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    79 => [
        'name'             => ['快捷', '不定位', '五星三码'],
        'result'           => ['exec' => 'SSCResult::resultBWxsm', 'args' => ['start' => 0, 'len' => 5]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1, 'everyNumberLength' => 3]],
        'valid'            => ['exec' => 'SSCValid::validMn', 'args' => ['count' => 3]],
        'standardOdds'     => [22.98],
        'standardOddsDesc' => ['五星三码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    80 => [
        'name'             => ['快捷', '大小单双', '单球'],
        'result'           => ['exec' => 'SSCResult::resultDDq', 'args' => ['start' => 0, 'len' => 5]],
        'format'           => ['exec' => 'SSCFormat::formatDwd', 'args' => ['playNumberLength' => 5, 'range' => ['大', '小', '单', '双']]],
        'valid'            => ['exec' => 'SSCValid::validDwd'],
        'standardOdds'     => [2],
        'standardOddsDesc' => ['单球'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
        // 'standardOddsDescAlias' => ['大', '小', '单', '双'],
    ],

    81 => [
        'name'                  => ['快捷', '大小单双', '后二'],
        'result'                => ['exec' => 'SSCResult::resultDHe', 'args' => ['start' => 3, 'len' => 2]],
        'format'                => ['exec' => 'SSCFormat::formatByConfKey', 'args' => ['field' => 'standardOddsDescAlias', 'playNumberLength' => 2]],
        'valid'                 => ['exec' => 'SSCValid::valid'],
        'standardOdds'          => [4],
        'standardOddsDesc'      => ['后二'],
        'standardOddsDescAlias' => ['大', '小', '单', '双'],
        'pretty'                => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    82 => [
        'name'                  => ['快捷', '大小单双', '前二'],
        'result'                => ['exec' => 'SSCResult::resultDHe', 'args' => ['start' => 0, 'len' => 2]],
        'format'                => ['exec' => 'SSCFormat::formatByConfKey', 'args' => ['field' => 'standardOddsDescAlias', 'playNumberLength' => 2]],
        'valid'                 => ['exec' => 'SSCValid::valid'],
        'standardOdds'          => [4],
        'standardOddsDesc'      => ['前二'],
        'standardOddsDescAlias' => ['大', '小', '单', '双'],
        'pretty'                => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    83 => [
        'name'                  => ['快捷', '大小单双', '后三'],
        'result'                => ['exec' => 'SSCResult::resultDHs', 'args' => ['start' => 2, 'len' => 3]],
        'format'                => ['exec' => 'SSCFormat::formatByConfKey', 'args' => ['field' => 'standardOddsDescAlias', 'playNumberLength' => 3]],
        'valid'                 => ['exec' => 'SSCValid::valid'],
        'standardOdds'          => [8],
        'standardOddsDesc'      => ['后三'],
        'standardOddsDescAlias' => ['大', '小', '单', '双'],
        'pretty'                => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    84 => [
        'name'                  => ['快捷', '大小单双', '前三'],
        'result'                => ['exec' => 'SSCResult::resultDHs', 'args' => ['start' => 0, 'len' => 3]],
        'format'                => ['exec' => 'SSCFormat::formatByConfKey', 'args' => ['field' => 'standardOddsDescAlias', 'playNumberLength' => 3]],
        'valid'                 => ['exec' => 'SSCValid::valid'],
        'standardOdds'          => [8],
        'standardOddsDesc'      => ['前三'],
        'standardOddsDescAlias' => ['大', '小', '单', '双'],
        'pretty'                => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ],

    85 => [
        'name'             => ['标准', '后三', '组选'],
        'result'           => ['exec' => 'SSCResult::resulth3zx', 'args' => ['start' => 2, 'len' => 3]],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 3]],
        'valid'            => ['exec' => 'SSCValid::valid', 'args' => ['defaultBetCount' => 3]],
        'standardOdds'     => [1000],
        'standardOddsDesc' => ['后三组选'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zx'],
    ],

    86 => [
        'name'             => ['标准', '不定位', '后二一码'],
        'result'           => ['exec' => 'SSCResult::resultBH2ym'],
        'format'           => ['exec' => 'SSCFormat::format', 'args' => ['playNumberLenth' => 1]],
        'valid'            => ['exec' => 'SSCValid::valid'],
        'standardOdds'     => [3.69],
        'standardOddsDesc' => ['后二一码'],
        'pretty'           => ['exec' => 'SSCPretty::pretty5Zxfs'],
    ]
];
