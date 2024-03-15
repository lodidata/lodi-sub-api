<?php
//801 - 950
return [
    // 全局(每个具体玩法里面定义后会覆盖)
    0 => [
        'periodCodeFormat' => ['exec' => 'M11N5Format::periodCodeFormatByRange', 'args' => ['range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'length' => 5, 'unique' => true]],

        'format' => ['exec' => 'M11N5Format::format', 'args' => ['playNumberLenth' => 5]],

        'valid' => ['exec' => 'M11N5Valid::valid', 'args' => ['defaultBetCount' => 1]],
    ],

    801 => [
        'name' => ['标准', '前三码', '直选复式'],
        'result' => ['exec' => 'M11N5Result::resultS3Zxfs'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 3, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]]],
        'valid' => ['exec' => 'M11N5Valid::validSb'],
        'standardOdds' => [990],
        'standardOddsDesc' => ['直选复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    802 => [
        'name' => ['标准', '前三码', '组选复式'],
        'result' => ['exec' => 'M11N5Result::resultS3Zxfs2'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 3]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 3]],
        'standardOdds' => [165],
        'standardOddsDesc' => ['组选复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    803 => [
        'name' => ['标准', '前三码', '组选胆拖'],
        'result' => ['exec' => 'M11N5Result::resultS3Zxfs2'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 2], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 3]],
        'standardOdds' => [165],
        'standardOddsDesc' => ['组选胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    804 => [
        'name' => ['标准', '前二码', '直选复式'],
        'result' => ['exec' => 'M11N5Result::resultS2Zxfs'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]]],
        'valid' => ['exec' => 'M11N5Valid::validSb'],
        'standardOdds' => [110],
        'standardOddsDesc' => ['直选复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    805 => [
        'name' => ['标准', '前二码', '组选复式'],
        'result' => ['exec' => 'M11N5Result::resultS2Zxfs2'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 2]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 2]],
        'standardOdds' => [55],
        'standardOddsDesc' => ['组选复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    806 => [
        'name' => ['标准', '前二码', '组选胆拖'],
        'result' => ['exec' => 'M11N5Result::resultS2Zxfs2'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 1], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 2]],
        'standardOdds' => [55],
        'standardOddsDesc' => ['组选胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    807 => [
        'name' => ['标准', '任选复式', '1中1复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 1]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'M11N5Valid::valid'],
        'standardOdds' => [2.2],
        'standardOddsDesc' => ['1中1复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    808 => [
        'name' => ['标准', '任选复式', '2中2复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 2]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 2]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 2]],
        'standardOdds' => [5.5],
        'standardOddsDesc' => ['2中2复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    809 => [
        'name' => ['标准', '任选复式', '3中3复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 3]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 3]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 3]],
        'standardOdds' => [16.5],
        'standardOddsDesc' => ['3中3复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    810 => [
        'name' => ['标准', '任选复式', '4中4复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 4]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 4]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 4]],
        'standardOdds' => [66],
        'standardOddsDesc' => ['4中4复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    811 => [
        'name' => ['标准', '任选复式', '5中5复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 5]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 5]],
        'standardOdds' => [462],
        'standardOddsDesc' => ['5中5复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    812 => [
        'name' => ['标准', '任选复式', '6中5复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 6]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 6]],
        'standardOdds' => [77],
        'standardOddsDesc' => ['6中5复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    813 => [
        'name' => ['标准', '任选复式', '7中5复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 7]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 7]],
        'standardOdds' => [22],
        'standardOddsDesc' => ['7中5复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    814 => [
        'name' => ['标准', '任选复式', '8中5复式'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => 8]],
        'valid' => ['exec' => 'M11N5Valid::validMn', 'args' => ['count' => 8]],
        'standardOdds' => [8.25],
        'standardOddsDesc' => ['8中5复式'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    815 => [
        'name' => ['标准', '任选胆拖', '2中2胆拖'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 2]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 1], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 2]],
        'standardOdds' => [5.5],
        'standardOddsDesc' => ['2中2胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    816 => [
        'name' => ['标准', '任选胆拖', '3中3胆拖'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 3]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 2], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 3]],
        'standardOdds' => [16.5],
        'standardOddsDesc' => ['3中3胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    817 => [
        'name' => ['标准', '任选胆拖', '4中4胆拖'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 4]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 3], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 4]],
        'standardOdds' => [66],
        'standardOddsDesc' => ['4中4胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    818 => [
        'name' => ['标准', '任选胆拖', '5中5胆拖'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 4], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 5]],
        'standardOdds' => [462],
        'standardOddsDesc' => ['5中5胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    819 => [
        'name' => ['标准', '任选胆拖', '6中5胆拖'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 5], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 6]],
        'standardOdds' => [77],
        'standardOddsDesc' => ['6中5胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    820 => [
        'name' => ['标准', '任选胆拖', '7中5胆拖'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 6], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 7]],
        'standardOdds' => [22],
        'standardOddsDesc' => ['7中5胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    821 => [
        'name' => ['标准', '任选胆拖', '8中5胆拖'],
        'result' => ['exec' => 'M11N5Result::resultSMzns', 'args' => ['length' => 5]],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'everyNumberLength' => [1, 7], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'M11N5Valid::validSb2', 'args' => ['count' => 8]],
        'standardOdds' => [8.25],
        'standardOddsDesc' => ['8中5胆拖'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    822 => [
        'name' => ['快捷', '定位', '定位球号'],
        'result' => ['exec' => 'M11N5Result::resultFDwd'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 5, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]]],
        'valid' => ['exec' => 'M11N5Valid::validDwd'],
        'standardOdds' => [11],
        'standardOddsDesc' => ['定位球号'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    823 => [
        'name' => ['快捷', '定位', '大小单双'],
        'result' => ['exec' => 'M11N5Result::resultFDsds'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 5, 'range' => ['大', '小', '单', '双']]],
        'valid' => ['exec' => 'M11N5Valid::validDwd'],
        'standardOdds' => [1.83, 2.2, 1.83, 2.2],
        'standardOddsDesc' => ['大', '小', '单', '双'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    824 => [
        'name' => ['快捷', '不定位', '不定位码'],
        'result' => ['exec' => 'M11N5Result::resultFBdwn'],
        'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]]],
        'valid' => ['exec' => 'M11N5Valid::validDwd'],
        'standardOdds' => [3.31],
        'standardOddsDesc' => ['不定位码'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    825 => [
        'name' => ['快捷', '趣味玩法', '龙虎斗'],
        'result' => ['exec' => 'M11N5Result::resultFlhd'],
        // 'format' => ['exec' => 'M11N5Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => ['一五龙', '一五虎', '二四龙', '二四虎']]],
        'format' => ['exec' => 'M11N5Format::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid' => ['exec' => 'M11N5Valid::validDwd'],
        'standardOdds' => [2],
        'standardOddsDesc' => ['龙虎斗'],
        'standardOddsDescAlias' => ['一五龙', '一五虎', '二四龙', '二四虎'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    826 => [
        'name' => ['快捷', '趣味玩法', '定向单双'],
        'result' => ['exec' => 'M11N5Result::resultFDxds'],
        'format' => ['exec' => 'M11N5Format::formatByOddsDesc'],
        'valid' => ['exec' => 'M11N5Valid::validDwd'],
        'standardOdds' => [77, 6.16, 2.31, 3.08, 15.4, 462],
        'standardOddsDesc' => ['5单0双', '4单1双', '3单2双', '2单3双', '1单4双', '0单5双'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],

    827 => [
        'name' => ['快捷', '趣味玩法', '定向中位'],
        'result' => ['exec' => 'M11N5Result::resultFDxzw'],
        'format' => ['exec' => 'M11N5Format::formatByOddsDesc'],
        'valid' => ['exec' => 'M11N5Valid::validDwd'],
        'standardOdds' => [16.5, 7.33, 5.13, 4.62, 5.13, 7.33, 16.5],
        'standardOddsDesc' => ['3', '4', '5', '6', '7', '8', '9'],
        'pretty' => ['exec' => 'M11N5Pretty::prettyDwd'],
    ],
];
