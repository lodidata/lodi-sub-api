<?php
$b110 = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$bbd = ['大', '小', '单', '双'];
$bld = ['龙', '虎'];
return [
    [
        'aliasName'  => '定位胆',
        'playId' => 451,
        'tags' => [
                ['nm' => '冠军', 'sv' => $b110, 'vv' => $b110],
                ['nm' => '亚军', 'sv' => $b110, 'vv' => $b110],
                ['nm' => '季军', 'sv' => $b110, 'vv' => $b110],
            ],
    ],

    [
        'aliasName'  => '大小单双',
        'playId' => 452,
        'tags' => [
                ['nm' => '冠军', 'sv' => $bbd, 'vv' => $bbd],
                ['nm' => '亚军', 'sv' => $bbd, 'vv' => $bbd],
                ['nm' => '季军', 'sv' => $bbd, 'vv' => $bbd],
        ],
    ],

    [
        'aliasName'  => '冠亚和',
        'playId' => 453,
        'tags' => [
            ['nm' => '和值', 'sv' => ['和大', '和小','和单','和双',
                                '3','4','5',
                                '6','7','8',
                                '9','10','11',
                                '12','13','14',
                                '15','16','17',
                                '18','19'], 'vv' => ['和大', '和小','和单','和双',
                                '3','4','5',
                                '6','7','8',
                                '9','10','11',
                                '12','13','14',
                                '15','16','17',
                                '18','19']],
        ],
    ],

    [
        'aliasName'  => '冠亚季和值',
        'playId' => 454,
        'tags' => [
            ['nm' => '和值', 'sv' => ['6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27'], 'vv' => ['6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27']],
        ],
    ],

    [
        'aliasName'  => '龙虎斗',
        'playId' => 455,
        'tags' => [
            ['nm' => '冠军vs第十', 'sv' => $bld, 'vv' => ['冠十龙', '冠十虎']],
            ['nm' => '亚军vs第九', 'sv' => $bld, 'vv' => ['亚九龙', '亚九虎']],
            ['nm' => '季军vs第八', 'sv' => $bld, 'vv' => ['季八龙', '季八虎']],
            ['nm' => '第四vs第七', 'sv' => $bld, 'vv' => ['四七龙', '四七虎']],
            ['nm' => '第五vs第六', 'sv' => $bld, 'vv' => ['五六龙', '五六虎']],
        ],
    ],
];