<?php
//151 - 300
return [
    // 全局(每个具体玩法里面定义后会覆盖)
    0 => [
        'periodCodeFormat' => ['exec' => 'XY28Format::periodCodeFormat', 'args' => ['playNumberLenth' => 3]],
        'format' => ['exec' => 'XY28Format::format', 'args' => ['playNumberLenth' => 3]],
        'valid' => ['exec' => 'XY28Valid::valid', 'args' => ['defaultBetCount' => 1]],
    ],

    151 => [
        'name' => ['快捷', '大小单双', '大小单双'],
        'result' => ['exec' => 'XY28Result::resultDDxds'],
        'format' => ['exec' => 'XY28Format::formatByOddsDesc'],
        'valid' => ['exec' => 'XY28Valid::valid'],
        'standardOdds' => [2.00, 2.00, 2.00, 2.00, 3.72, 4.33, 4.33, 3.72, 17.86, 17.86],
        'standardOddsDesc' => [
            '大', '小', '单', '双',
            '小单', '小双', '大单', '大双',
            '极小', '极大',
        ],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],

    152 => [
        'name' => ['快捷', '色波豹子', '色波'],
        'result' => ['exec' => 'XY28Result::resultSSb'],
        'format' => ['exec' => 'XY28Format::formatByOddsDesc'],
        'valid' => ['exec' => 'XY28Valid::valid'],
        'standardOdds' => [3.01, 3.88, 3.88],
        'standardOddsDesc' => ['红波', '绿波', '蓝波'],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],

    153 => [
        'name' => ['快捷', '色波豹子', '豹子'],
        'result' => ['exec' => 'XY28Result::resultSBz'],
        // 'format' => ['exec' => 'XY28Format::format3bsd', 'args' => ['type' => 0]],
        'format' => ['exec' => 'XY28Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'XY28Valid::valid'],
        'standardOdds' => [100, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000],
        'standardOddsDesc' => ['豹子通选', '000', '111', '222', '333', '444', '555', '666', '777', '888', '999'],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],

    154 => [
        'name' => ['快捷', '色波豹子', '顺子'],
        'result' => ['exec' => 'XY28Result::resultSBz2'],
        // 'format' => ['exec' => 'XY28Format::format3bsd', 'args' => ['type' => 1]],
        'format' => ['exec' => 'XY28Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'XY28Valid::valid'],
        'standardOdds' => [16.67, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66, 166.66],
        'standardOddsDesc' => ['顺子通选', '012', '123', '234', '345', '456', '567', '678', '789', '890', '901'],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],

    155 => [
        'name' => ['快捷', '色波豹子', '对子'],
        'result' => ['exec' => 'XY28Result::resultSDz'],
        // 'format' => ['exec' => 'XY28Format::format3bsd', 'args' => ['type' => 2]],
        'format' => ['exec' => 'XY28Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'XY28Valid::valid'],
        'standardOdds' => [3.7, 37, 37, 37, 37, 37, 37, 37, 37, 37, 37],
        'standardOddsDesc' => ['对子通选', '00', '11', '22', '33', '44', '55', '66', '77', '88', '99'],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],

    156 => [
        'name' => ['快捷', '猜和值', '和值'],
        'result' => ['exec' => 'XY28Result::resultCHz'],
        'format' => ['exec' => 'XY28Format::formatZxhz'],
        'valid' => ['exec' => 'XY28Valid::valid'],
        'standardOdds' => [1000, 333.33, 166.67, 100, 66.67, 47.62, 35.71, 27.78, 22.22, 18.18, 15.87, 14.49, 13.70, 13.33, 13.33, 13.70, 14.49, 15.87, 18.18, 22.22, 27.78, 35.71, 47.62, 66.67, 100.00, 166.67, 333.33, 1000.00],
        'standardOddsDesc' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27'],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],

    157 => [
        'name' => ['快捷', '猜和值', '和值包三'],
        'result' => ['exec' => 'XY28Result::resultCHzbs'],
        'format' => ['exec' => 'XY28Format::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27], 'everyNumberLength' => 3]],
        'valid' => ['exec' => 'XY28Valid::validMn', 'args' => ['count' => 3]],
        'standardOdds' => [3.5],
        'standardOddsDesc' => ['和值包三'],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],

    // 158 => [
    //         'name' => ['猜数字', '猜数字', '和值包三'],
    //         'result' => ['exec' => 'XY28Result::resultCHzbs'],
    //         'format' => ['exec' => 'XY28Format::formatZxhz', 'args' => ['max' => 27, 'playNumberLenth' => 1, 'everyNumberLength' => 3]],
    //         'valid' => ['exec' => 'XY28Valid::validMn', 'args' => ['count' => 3]],
    //         'standardOdds' => [3.7],
    //         'standardOddsDesc' => ['和值包三'],
    // ],

    159 => [
        'name' => ['快捷', '龙虎斗', '龙虎斗'],
        'result' => ['exec' => 'XY28Result::resultCLHd'],
        'format' => ['exec' => 'XY28Format::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid' => ['exec' => 'XY28Valid::valid'],
        'standardOdds' => [2.22],
        'standardOddsDesc' => ['龙虎斗'],
        'standardOddsDescAlias' => ['百十龙', '百十虎', '百个龙', '百个虎', '十个龙', '十个虎'],
        'pretty' => ['exec' => 'Xy28Pretty::prettyDwd'],
    ],
];