<?php
$b111 = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'];
$b111v = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
$bbd = ['大', '小', '单', '双'];
$bld = ['龙', '虎'];
return [
    [
        'aliasName'  => '前三组选',
        'playId' => 802,
        'tags' => [
            ['nm' => '组选', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '前二组选',
        'playId' => 805,
        'tags' => [
            ['nm' => '组选', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '一中一',
        'playId' => 807,
        'tags' => [
            ['nm' => '选一中一', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '二中二',
        'playId' => 808,
        'tags' => [
            ['nm' => '选二中二', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '三中三',
        'playId' => 809,
        'tags' => [
            ['nm' => '选三中三', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

        [
        'aliasName'  => '四中四',
        'playId' => 810,
        'tags' => [
            ['nm' => '选四中四', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '五中五',
        'playId' => 811,
        'tags' => [
            ['nm' => '选五中五', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '六中五',
        'playId' => 812,
        'tags' => [
            ['nm' => '选六中五', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '七中五',
        'playId' => 813,
        'tags' => [
            ['nm' => '选七中五', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '八中五',
        'playId' => 814,
        'tags' => [
            ['nm' => '选八中五', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '定位球号',
        'playId' => 822,
        'tags' => [
            ['nm' => '第一球', 'sv' => $b111, 'vv' => $b111v],
            ['nm' => '第二球', 'sv' => $b111, 'vv' => $b111v],
            ['nm' => '第三球', 'sv' => $b111, 'vv' => $b111v],
            ['nm' => '第四球', 'sv' => $b111, 'vv' => $b111v],
            ['nm' => '第五球', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '大小单双',
        'playId' => 823,
        'tags' => [
            ['nm' => '第一球', 'sv' => $bbd, 'vv' => $bbd],
            ['nm' => '第二球', 'sv' => $bbd, 'vv' => $bbd],
            ['nm' => '第三球', 'sv' => $bbd, 'vv' => $bbd],
            ['nm' => '第四球', 'sv' => $bbd, 'vv' => $bbd],
            ['nm' => '第五球', 'sv' => $bbd, 'vv' => $bbd],
        ],
    ],

    [
        'aliasName'  => '不定位码',
        'playId' => 824,
        'tags' => [
            ['nm' => '前三位', 'sv' => $b111, 'vv' => $b111v],
        ],
    ],

    [
        'aliasName'  => '龙虎斗',
        'playId' => 825,
        'tags' => [
            ['nm' => '一球vs五球', 'sv' => $bld, 'vv' => ['一五龙', '一五虎']],
            ['nm' => '二球vs四球', 'sv' => $bld, 'vv' => ['二四龙', '二四虎']],
        ],
    ],

    [
        'aliasName'  => '定向单双',
        'playId' => 826,
        'tags' => [
            ['nm' => '定向单双', 'sv' => ['5单0双', '4单1双', '3单2双', '2单3双', '1单4双', '0单5双'], 'vv' => ['5单0双', '4单1双', '3单2双', '2单3双', '1单4双', '0单5双']],
        ],
    ],

    [
        'aliasName'  => '猜中位',
        'playId' => 827,
        'tags' => [
            ['nm' => '猜中位', 'sv' => ['03','04','05','06','07','08','09'], 'vv' => ['3','4','5','6','7','8','9']],
        ],
    ],
];