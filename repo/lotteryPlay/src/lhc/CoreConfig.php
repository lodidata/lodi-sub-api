<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/7/5
 * Time: 15:25
 */


return [

    0 => [
        'periodCodeFormat' => ['exec' => 'LHCFormat::periodCodeFormat3', 'args' => ['playNumberLenth' => 7]],
        'format' => ['exec' => 'LHCFormat::format', 'args' => ['playNumberLenth' => 7]],
        'valid' => ['exec' => 'LHCValid::valid', 'args' => ['defaultBetCount' => 1]],
    ],

    510=>[
        'name' => ['快捷', '猜特码', '特码号'],
        'result' => ['exec' => 'LHCResult::resultTtmh'],
       /* 'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00, 49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00,49.00],
        'standardOddsDesc' => [
            "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"
        ],*/
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49],
        'standardOddsDesc' => [
            "正码号"
        ],

        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    511=>[
        'name' => [  '快捷',  '猜特码',  '特码双面',],
        'result' => ['exec' => 'LHCResult::resultTtmsm'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [2.00, 2.00, 2.00, 2.00, 4.00, 4.00, 4.00, 4.00,2.00,2.00,2.00,2.00,2.00,2.00],
        'standardOddsDesc' => ['大', '小', '单', '双', '小单', '小双', '大单', '大双','和大','和小','和单','和双','尾大','尾小'],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],

    512=>[
        'name' => [  '快捷','猜正码', '正码号',],
        'result' => ['exec' => 'LHCResult::resultZmh'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [8.16],
        'standardOddsDesc' => [
            "正码号"
        ],
        //'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
       /* 'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,8.16,],
        'standardOddsDesc' => [
            "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"
        ],*/
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    513=>[
        'name' => ['快捷','十二生肖', '特码生肖',],
        'result' => ['exec' => 'LHCResult::ResultTtmsx'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 12.25, 9.8, 12.25,],
        'standardOddsDesc' => [
            '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    514=>[
        'name' => [  '快捷', '十二生肖', '正码生肖',],
        'result' => ['exec' => 'LHCResult::resultZzmsx'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [2.04, 2.04,2.04,2.04,2.04,2.04,2.04,2.04,2.04,2.04,1.96,2.04],
        'standardOddsDesc' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪',],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    515=>[
        'name' => [  '快捷', '十二生肖', '正特生肖',],
        'result' => ['exec' => 'LHCResult::ResultZztsx'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [2.12, 2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,1.8,2.12],
        'standardOddsDesc' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪',],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    516=>[
        'name' => [ '快捷', '猜色波', '特码色波',],
        'result' => ['exec' => 'LHCResult::ResultTtmsb'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [2.08,3.06,3.06],
        'standardOddsDesc' => [
            0 => '红波',
            1 => '蓝波',
            2 => '绿波',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    517=>[
        'name' => [  '快捷', '猜色波', '特码半波',],
        'result' => ['exec' => 'LHCResult::ResultTtmbb'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [6.0, 5.33, 6.85, 4.80, 6.0, 6.0, 5.33, 6.85, 6.0, 6.85, 6.0, 6.85,],
        'standardOddsDesc' => ['红单', '红双', '红大', '红小', '蓝单', '蓝双', '蓝大', '蓝小', '绿单', '绿双', '绿大', '绿小',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    518=>[
        'name' => [  '快捷', '猜色波', '特码三波',],
        'result' => ['exec' => 'LHCResult::ResultTmsb'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [ 16.0, 12.0, 9.60, 9.60, 9.60, 12.00, 16.00, 12.00, 12.00, 12.00, 12.00, 16.00,],
        'standardOddsDesc' => ['红大单', '红大双', '红小单', '红小双', '蓝大单', '蓝大双', '蓝小单', '蓝小双', '绿大单', '绿大双', '绿小单', '绿小双',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    519=>[
        'name' => [  '快捷', '正特头尾', '特码头数',],
        'result' => ['exec' => 'LHCResult::ResultTtmts'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [5.40,4.90,4.90,4.90,4.90],
        'standardOddsDesc' => ['头0', '头1', '头2', '头3', '头4',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    520=>[
        'name' => [  '快捷', '正特头尾', '特码尾数',],
        'result' => ['exec' => 'LHCResult::resultTtmws'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [12.25, 9.80,9.80,9.80,9.80,9.80,9.80,9.80,9.80,9.80,],
        'standardOddsDesc' => ['尾0', '尾1', '尾2', '尾3', '尾4', '尾5', '尾6', '尾7', '尾8', '尾9',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    521=>[
        'name' => [  '快捷', '正特头尾', '正码尾数',],
        'result' => ['exec' => 'LHCResult::resultZzmws'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [2.30, 2.01,2.01,2.01,2.01,2.01,2.01,2.01,2.01,2.01,],
        'standardOddsDesc' => ['尾0', '尾1', '尾2', '尾3', '尾4', '尾5', '尾6', '尾7', '尾8', '尾9',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    522=>[
        'name' => [   '快捷', '正A定位（单号）', '正码一',],
        'result' => ['exec' => 'LHCResult::resultZmy'],
        //'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49.00],
        'standardOddsDesc' => [
            "正码一"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    523=>[
        'name' => [   '快捷', '正A定位（单号）', '正码二',],
        'result' => ['exec' => 'LHCResult::resultZme'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49.00],
        'standardOddsDesc' => [
            "正码二"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    524=>[
        'name' => [   '快捷', '正A定位（单号）', '正码三',],
        'result' => ['exec' => 'LHCResult::resultZms'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49.00],
        'standardOddsDesc' => [
            "正码三"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    525=>[
        'name' => [   '快捷', '正A定位（单号）', '正码四',],
        'result' => ['exec' => 'LHCResult::resultZmsi'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49.00],
        'standardOddsDesc' => [
            "正码四"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    526=>[
        'name' => [   '快捷', '正A定位（单号）', '正码五',],
        'result' => ['exec' => 'LHCResult::resultZmw'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49.00],
        'standardOddsDesc' => [
            "正码五"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    527=>[
        'name' => [   '快捷', '正A定位（单号）', '正码六',],
        'result' => ['exec' => 'LHCResult::resultZml'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [49.00],
        'standardOddsDesc' => [
            "正码六"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    528=>[
        'name' => [ '快捷', '正B定位(双面)', '正码一',],
        'result' => ['exec' => 'LHCResult::ResultZzmy'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,],
        'standardOddsDesc' => ['大', '小', '单', '双',
             '大单', '小单', '大双', '小双',
            '和大', '和小', '和单', '和双',
             '尾大', '尾小',
             '红波', '蓝波', '绿波',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    529=>[
        'name' => ['快捷', '正B定位(双面)', '正码二'],
        'result' => ['exec' => 'LHCResult::ResultZzme'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [   2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,],
        'standardOddsDesc' => ['大', '小', '单', '双',
            '大单', '小单', '大双', '小双',
            '和大', '和小', '和单', '和双',
            '尾大', '尾小',
            '红波', '蓝波', '绿波',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    530=>[
        'name' => ['快捷', '正B定位(双面)', '正码三'],
        'result' => ['exec' => 'LHCResult::ResultZzms'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [   2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,],
        'standardOddsDesc' => ['大', '小', '单', '双',
            '大单', '小单', '大双', '小双',
            '和大', '和小', '和单', '和双',
            '尾大', '尾小',
            '红波', '蓝波', '绿波',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    531=>[
        'name' => ['快捷', '正B定位(双面)', '正码四'],
        'result' => ['exec' => 'LHCResult::ResultZzmsi'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [   2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,],
        'standardOddsDesc' => ['大', '小', '单', '双',
            '大单', '小单', '大双', '小双',
            '和大', '和小', '和单', '和双',
            '尾大', '尾小',
            '红波', '蓝波', '绿波',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    532=>[
        'name' => ['快捷', '正B定位(双面)', '正码五'],
        'result' => ['exec' => 'LHCResult::ResultZzmw'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [   2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,],
        'standardOddsDesc' => ['大', '小', '单', '双',
            '大单', '小单', '大双', '小双',
            '和大', '和小', '和单', '和双',
            '尾大', '尾小',
            '红波', '蓝波', '绿波',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    533=>[
        'name' => ['快捷', '正B定位(双面)', '正码六'],
        'result' => ['exec' => 'LHCResult::ResultZzml'],
        'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
        'valid' => ['exec' => 'LHCValid::valid'],
        'standardOdds' => [   2.0, 2.0, 2.0, 2.0,
            4.0, 4.0, 4.0, 4.0,
            2.0, 2.0, 2.0, 2.0,
            2.0, 2.0,
            2.88, 3.06, 3.06,],
        'standardOddsDesc' => ['大', '小', '单', '双',
            '大单', '小单', '大双', '小双',
            '和大', '和小', '和单', '和双',
            '尾大', '尾小',
            '红波', '蓝波', '绿波',
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    534=>[
        'name' => [  '快捷', '任选中一', '五中一',],
        /*'format' => ['exec' => 'LHCFormat::formatByOddsDesc2'],
      'valid' => ['exec' => 'LHCValid::validMn','args' => ['count' => 5]],
      'standardOdds' => [2.43, 2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,2.43,],
      'standardOddsDesc' => [
          "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"
      ],*/
        'result' => ['exec' => 'LHCResult::resultBaseRenxuan'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 1]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 5,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["2.43"],
        'standardOddsDesc' => [
            "五中一"
        ],


        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    535=>[
        'name' => ['快捷', '任选中一', '六中一',],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuan'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 6]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 6,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["2.34"],
        'standardOddsDesc' => [
            "六中一"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    536=>[
        'name' => ['快捷', '任选中一', '七中一'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuan'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 7]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 7,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["2.33"],
        'standardOddsDesc' => [
            "七中一"
        ],

        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    537=>[
        'name' => ['快捷', '任选中一', '八中一'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuan'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 8]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 8,'defaultbetCount'=>1,'maxNumberCount'=>11]],
        'standardOdds' => ["2.38"],
        'standardOddsDesc' => [
            "八中一"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    538=>[
        'name' => ['快捷', '任选中一', '九中一'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuan'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 9]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 9,'defaultbetCount'=>1,'maxNumberCount'=>12]],
        'standardOdds' => ["2.48"],
        'standardOddsDesc' => [
            "九中一"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    539=>[
        'name' => ['快捷', '任选中一', '十中一'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuan'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 10]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 10,'defaultbetCount'=>1,'maxNumberCount'=>13]],
        'standardOdds' => ["2.48"],
        'standardOddsDesc' => [
            "十中一"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    540=>[
        'name' => ['快捷', '任选不中', '五不中'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuanNo','args'=>['length'=>5]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 5]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 5,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["2.24"],
        'standardOddsDesc' => [
            "五不中"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    541=>[
        'name' => ['快捷', '任选不中', '六不中'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuanNo','args'=>['length'=>6]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 6]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 6,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["2.26"],
        'standardOddsDesc' => [
            "六不中"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    542=>[
        'name' => ['快捷', '任选不中', '七不中'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuanNo','args'=>['length'=>7]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 7]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 7,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["3.18"],
        'standardOddsDesc' => [
            "七不中"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    543=>[
        'name' => ['快捷', '任选不中', '八不中'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuanNo','args'=>['length'=>8]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 8]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 8,'defaultbetCount'=>1,'maxNumberCount'=>11]],
        'standardOdds' => ["3.18"],
        'standardOddsDesc' => [
            "八不中"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    544=>[
        'name' => ['快捷', '任选不中', '九不中'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuanNo','args'=>['length'=>9]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 9]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 9,'defaultbetCount'=>1,'maxNumberCount'=>12]],
        'standardOdds' => ["4.60"],
        'standardOddsDesc' => [
            "九不中"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    545=>[
        'name' => ['快捷', '任选不中', '十不中'],
        'result' => ['exec' => 'LHCResult::resultBaseRenxuanNo','args'=>['length'=>10]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 10]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 10,'defaultbetCount'=>1,'maxNumberCount'=>13]],
        'standardOdds' => ["5.58"],
        'standardOddsDesc' => [
            "十不中"
        ],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    //2019-06-05 add
    546=>[
        'name' => ['快捷', '连码', '三中二'],
        'result' => ['exec' => 'LHCResult::ResultSze'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 3]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 3,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["2", "10"],
        'standardOddsDesc' => ["中二", "中三"],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    547=>[
        'name' => ['快捷', '连码', '三全中'],
        'result' => ['exec' => 'LHCResult::ResultSqz'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 3]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 3,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["6.5"],
        'standardOddsDesc' => ["三全中"],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    548=>[
        'name' => ['快捷', '连码', '二全中'],
        'result' => ['exec' => 'LHCResult::ResultEqz'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 2]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 2,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["6.3"],
        'standardOddsDesc' => ["二全中"],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    549=>[
        'name' => ['快捷', '连码', '二中特'],
        'result' => ['exec' => 'LHCResult::ResultEzt'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 2]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 2,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["5.1", "3.1"],
        'standardOddsDesc' => ["中二", "中特"],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    550=>[
        'name' => ['快捷', '连码', '特串'],
        'result' => ['exec' => 'LHCResult::ResultTec'],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => [ "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "40", "41", "42", "43", "44", "45", "46", "47", "48", "49"], 'everyNumberLength' => 2]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 2,'defaultbetCount'=>1,'maxNumberCount'=>10]],
        'standardOdds' => ["15.5"],
        'standardOddsDesc' => ["特串"],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    //2019-12-20 add
    551=>[
        'name' => ['快捷', '连肖', '二连肖'],
        'result' => ['exec' => 'LHCResult::ResultLianXiao', 'args'=>['length'=>2]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'], 'everyNumberLength' => 2]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 2, 'defaultbetCount'=>1, 'maxNumberCount'=>12]],
        'standardOdds' => [2.12, 2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,1.8,2.12],
        'standardOddsDesc' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    552=>[
        'name' => ['快捷', '连肖', '三连肖'],
        'result' => ['exec' => 'LHCResult::ResultLianXiao', 'args'=>['length'=>3]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'], 'everyNumberLength' => 3]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 3, 'defaultbetCount'=>1, 'maxNumberCount'=>12]],
        'standardOdds' => [2.12, 2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,1.8,2.12],
        'standardOddsDesc' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    553=>[
        'name' => ['快捷', '连肖', '四连肖'],
        'result' => ['exec' => 'LHCResult::ResultLianXiao', 'args'=>['length'=>4]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'], 'everyNumberLength' => 4]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 4, 'defaultbetCount'=>1, 'maxNumberCount'=>12]],
        'standardOdds' => [2.12, 2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,1.8,2.12],
        'standardOddsDesc' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
    554=>[
        'name' => ['快捷', '连肖', '五连肖'],
        'result' => ['exec' => 'LHCResult::ResultLianXiao', 'args'=>['length'=>5]],
        'format' => ['exec' => 'LHCFormat::formatDwd', 'args' => ['playNumberLength' => 1, 'range' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'], 'everyNumberLength' => 5]],
        'valid' => ['exec' => 'LHCValid::validR','args' => ['count' => 5, 'defaultbetCount'=>1, 'maxNumberCount'=>12]],
        'standardOdds' => [2.12, 2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,2.12,1.8,2.12],
        'standardOddsDesc' => ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'],
        'pretty' => ['exec' => 'LHCPretty::prettyDwd'],
    ],
];
