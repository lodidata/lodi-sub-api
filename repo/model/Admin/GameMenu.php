<?php
/**
 * User: nk
 * Date: 2019-01-14
 * Time: 15:42
 * Des :
 */

namespace Model\Admin;

use DB;

/**
 * Class GameMenu 首页菜单
 * @package Model
 */
class GameMenu extends LogicModel
{
    protected $table = 'game_menu';
    public $timestamps = false;

    public $desc_attributes = [
        'status' => [
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [              //可为字符串，为字符串 ，说明
                'enabled' => '开启',
                'disabled' => '关闭'
            ]
        ],
        'across_status' => [
            'multi' => false,
            'value' => false,  //结果直接带上去 写在备注小（）内
            'key' => [              //可为字符串，为字符串 ，说明
                'enabled' => '横排游戏开关：开启',
                'disabled' => '横排游戏开关：关闭'
            ]
        ]
    ];


}