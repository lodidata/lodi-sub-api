<?php
//451 - 600
return [
    // 全局(每个具体玩法里面定义后会覆盖)
    0 => [
        'periodCodeFormat' => ['exec' => 'PK10Format::periodCodeFormat2'],

        'format' => ['exec' => 'PK10Format::format', 'args' => ['playNumberLenth' => 10]],

        'valid' => ['exec' => 'PK10Valid::valid', 'args' => ['defaultBetCount' => 1]],
    ],

    451 => [
        'name' => ['快捷', '定位胆', '定位胆'],
        'result' => ['exec' => 'PK10Result::resultDDwd'],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => [3, 10], 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validDwd'],
        'standardOdds' => [10],
        'standardOddsDesc' => ['定位胆'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd']
    ],

    452 => [
        'name' => ['快捷', '大小单双', '大小单双'],
        'result' => ['exec' => 'PK10Result::resultQDxds'],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 3, 'range' => ['大', '小', '单', '双']]],
        'valid' => ['exec' => 'PK10Valid::validDwd'],
        'standardOdds' => [2],
        'standardOddsDesc' => ['大小单双'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
        // 'standardOddsDescAlias' => ['大', '小', '单', '双'],
    ],

    453 => [
        'name' => ['快捷', '龙虎斗', '冠亚和值'],
        'result' => ['exec' => 'PK10Result::resultQGyhz'],
        'format' => ['exec' => 'PK10Format::formatByOddsDesc2'],
        'valid' => ['exec' => 'PK10Valid::valid'],
        'standardOdds' => [2.25, 1.8, 1.8, 2.25, 45, 45, 22.5, 22.5, 15, 15, 11.25, 11.25, 9, 11.25, 11.25, 15, 15, 22.5, 22.5, 45, 45],
        'standardOddsDesc' => ['和大', '和小', '和单', '和双',
            '3', '4', '5',
            '6', '7', '8',
            '9', '10', '11',
            '12', '13', '14',
            '15', '16', '17',
            '18', '19'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    454 => [
        'name' => ['快捷', '猜和值', '冠亚季和值'],
        'result' => ['exec' => 'PK10Result::resultQGyjhz'],
        'format' => ['exec' => 'PK10Format::formatByOddsDesc'],
        'valid' => ['exec' => 'PK10Valid::valid'],
        'standardOdds' => [120, 120, 60, 40, 30, 24, 17.14, 15, 13.33, 12, 12, 12, 12, 13.33, 15, 17.14, 24, 30, 40, 60, 120, 120],
        'standardOddsDesc' => ['6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    455 => [
        'name' => ['快捷', '龙虎斗', '龙虎斗'],
        'result' => ['exec' => 'PK10Result::resultQLhd'],
        'format' => ['exec' => 'PK10Format::formatByOddsDesc2', 'args' => ['field' => 'standardOddsDescAlias']],
        'valid' => ['exec' => 'PK10Valid::valid'],
        'standardOdds' => [2],
        'standardOddsDesc' => ['龙虎斗'],
        'standardOddsDescAlias' => ['冠十龙', '冠十虎', '亚九龙', '亚九虎', '季八龙', '季八虎', '四七龙', '四七虎', '五六龙', '五六虎'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    456 => [
        'name' => ['标准', '猜第一', '猜第一'],
        'result' => ['exec' => 'PK10Result::resultCCdy'],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 1, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::valid'],
        'standardOdds' => [10],
        'standardOddsDesc' => ['猜第一'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    457 => [
        'name' => ['标准', '猜前二', '猜前二'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 2]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 2, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [90],
        'standardOddsDesc' => ['猜前二'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    458 => [
        'name' => ['标准', '猜前三', '猜前三'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 3]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 3, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [720],
        'standardOddsDesc' => ['猜前三'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    460 => [
        'name' => ['标准', '猜前四', '猜前四'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 4]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 4, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [5040],
        'standardOddsDesc' => ['猜前四'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    461 => [
        'name' => ['标准', '猜前五', '猜前五'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 5]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 5, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [30240],
        'standardOddsDesc' => ['猜前五'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    462 => [
        'name' => ['标准', '猜前六', '猜前六'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 6]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 6, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [151200],
        'standardOddsDesc' => ['猜前六'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    463 => [
        'name' => ['标准', '猜前七', '猜前七'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 7]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 7, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [604800],
        'standardOddsDesc' => ['猜前七'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    464 => [
        'name' => ['标准', '猜前八', '猜前八'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 8]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 8, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [1814400],
        'standardOddsDesc' => ['猜前八'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

    465 => [
        'name' => ['标准', '猜前九', '猜前九'],
        'result' => ['exec' => 'PK10Result::resultCCde', 'args' => ['length' => 9]],
        'format' => ['exec' => 'PK10Format::formatDwd', 'args' => ['playNumberLenth' => 9, 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]],
        'valid' => ['exec' => 'PK10Valid::validS'],
        'standardOdds' => [3628800],
        'standardOddsDesc' => ['猜前九'],
        'pretty' => ['exec' => 'PK10Pretty::prettyDwd'],
    ],

];
