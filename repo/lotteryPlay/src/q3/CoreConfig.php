<?php
//601 - 750
return [
    // 全局(每个具体玩法里面定义后会覆盖)
    0 => [
        'periodCodeFormat' => ['exec' => 'Q3Format::periodCodeFormat', 'args' => ['playNumberLenth' => 3, 'sort' => 'asc']],

        'format' => ['exec' => 'Q3Format::format', 'args' => ['playNumberLenth' => 3]],

        'valid' => ['exec' => 'Q3Valid::valid', 'args' => ['defaultBetCount' => 1]],
    ],

    601 => [
        'name' => ['标准', '二不同号', '二不同标准'],
        'result' => ['exec' => 'Q3Result::resultEEbtbz'],
        'format' => ['exec' => 'Q3Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6]]],
        'valid' => ['exec' => 'Q3Valid::validMn', 'args' => ['count' => 2]],
        'standardOdds' => [7.2],
        'standardOddsDesc' => ['二不同标准'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    602 => [
        'name' => ['标准', '二不同号', '二不同胆拖'],
        'result' => ['exec' => 'Q3Result::resultEEbtbt'],
        'format' => ['exec' => 'Q3Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6], 'everyNumberLength' => [1, 1], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'Q3Valid::validSb2', 'args' => ['count' => 2]],
        'standardOdds' => [7.2],
        'standardOddsDesc' => ['二不同胆拖'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    603 => [
        'name' => ['标准', '二同号', '二同号标准'],
        'result' => ['exec' => 'Q3Result::resultEEthbz2'],
        'format' => ['exec' => 'Q3Format::formatDwdRange', 'args' => ['playNumberLenth' => 2, 'everyNumberLength' => 1, 'range' => [[11, 22, 33, 44, 55, 66], [1, 2, 3, 4, 5, 6]]]],
        'valid' => ['exec' => 'Q3Valid::validSEbh'],
        'standardOdds' => [72],
        'standardOddsDesc' => ['二同号标准'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    604 => [
        'name' => ['标准', '二同号', '二同号复式'],
        'result' => ['exec' => 'Q3Result::resultEEthbz22'],
        'format' => ['exec' => 'Q3Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => ['11*', '22*', '33*', '44*', '55*', '66*']]],
        'valid' => ['exec' => 'Q3Valid::validSEbh2'],
        'standardOdds' => [14.4],
        'standardOddsDesc' => ['二同号复式'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    605 => [
        'name' => ['标准', '三不同号', '三不同标准'],
        'result' => ['exec' => 'Q3Result::resultSEbtbz'],
        'format' => ['exec' => 'Q3Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6]]],
        'valid' => ['exec' => 'Q3Valid::validMn', 'args' => ['count' => 3]],
        'standardOdds' => [36],
        'standardOddsDesc' => ['三不同标准'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    606 => [
        'name' => ['标准', '三不同号', '三不同胆拖'],
        'result' => ['exec' => 'Q3Result::resultSEbtbz'],
        'format' => ['exec' => 'Q3Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6], 'everyNumberLength' => [1, 2], 'mutex' => true, 'everyNumberLengthOR' => true]],
        'valid' => ['exec' => 'Q3Valid::validSb2', 'args' => ['count' => 3]],
        'standardOdds' => [36],
        'standardOddsDesc' => ['三不同胆拖'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    607 => [
        'name' => ['标准', '三同号', '三同号'],
        'result' => ['exec' => 'Q3Result::resultSSth'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [216, 216, 216, 216, 216, 216, 36],
        'standardOddsDesc' => ['111', '222', '333', '444', '555', '666', '三同号通选'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    608 => [
        'name' => ['标准', '三同号', '三连号'],
        'result' => ['exec' => 'Q3Result::resultSSlh'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [36, 36, 36, 36, 9],
        'standardOddsDesc' => ['123', '234', '345', '456', '三连号通选'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    609 => [
        'name' => ['快捷', '点数', '点数'],
        'result' => ['exec' => 'Q3Result::resultSds'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [2.05, 2.05, 2.05, 2.05, 216, 72, 36, 21.6, 14.4, 10.29, 8.64, 8, 8, 8.64, 10.29, 14.4, 21.6, 36, 72, 216],
        'standardOddsDesc' => ['大', '小', '单', '双', 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    // 玩法不再使用
    610 => [
        'name' => ['快捷', '骰宝', '骰宝'],
        'result' => ['exec' => 'Q3Result::resultStb'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [2.37, 2.37, 2.37, 2.37, 2.37, 2.37, 13.5, 13.5, 13.5, 13.5, 13.5, 13.5, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9],
        'standardOddsDesc' => ['骰宝1', '骰宝2', '骰宝3', '骰宝4', '骰宝5', '骰宝6',
            '短牌11', '短牌22', '短牌33', '短牌44', '短牌55', '短牌66',
            '长牌12', '长牌13', '长牌14', '长牌15', '长牌16', '长牌23', '长牌24',
            '长牌25', '长牌26', '长牌34', '长牌35', '长牌36', '长牌45', '长牌46', '长牌56'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    611 => [
        'name' => ['快捷', '骰宝', '骰宝'],
        'result' => ['exec' => 'Q3Result::resultStb2'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [2.37],
        'standardOddsDesc' => ['骰宝'],
        'standardOddsDescAlias' => ['骰宝1', '骰宝2', '骰宝3', '骰宝4', '骰宝5', '骰宝6'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    612 => [
        'name' => ['快捷', '骰宝', '短牌'],
        'result' => ['exec' => 'Q3Result::resultStb2'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [13.5],
        'standardOddsDesc' => ['骰宝'],
        'standardOddsDescAlias' => ['骰宝1', '骰宝2', '骰宝3', '骰宝4', '骰宝5', '骰宝6',
            '短牌11', '短牌22', '短牌33', '短牌44', '短牌55', '短牌66',
            '长牌12', '长牌13', '长牌14', '长牌15', '长牌16', '长牌23', '长牌24',
            '长牌25', '长牌26', '长牌34', '长牌35', '长牌36', '长牌45', '长牌46', '长牌56'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    613 => [
        'name' => ['快捷', '骰宝', '长牌'],
        'result' => ['exec' => 'Q3Result::resultStb2'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [9],
        'standardOddsDesc' => ['长牌'],
        'standardOddsDescAlias' => ['长牌12', '长牌13', '长牌14', '长牌15', '长牌16', '长牌23', '长牌24',
            '长牌25', '长牌26', '长牌34', '长牌35', '长牌36', '长牌45', '长牌46', '长牌56'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],

    614 => [
        'name' => ['快捷', '点数', '大小单双'],
        'result' => ['exec' => 'Q3Result::resultSds'],
        'format' => ['exec' => 'Q3Format::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid' => ['exec' => 'Q3Valid::valid'],
        'standardOdds' => [2.05],
        'standardOddsDesc' => ['大小单双'],
        'standardOddsDescAlias' => ['大', '小', '单', '双'],
        'pretty' => ['exec' => 'Q3Pretty::prettyDwd'],
    ],
];
