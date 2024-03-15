<?php
use Utils\Www\Action;
use Model\Advert;
use Utils\Client;
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "h5首页菜单列表";
    const DESCRIPTION = "h5首页菜单列表";
    const TAGS = '首页';
    const SCHEMAS = [
           "hotgame" => [
               [
                   "cid" => "int() #游戏分类id",
                   "id" => "string() #mainNav-cp",
                   "name" => "string() #名字",
                   "alias" => "string() #英文名",
                   "enabled" => "int() #是否开启",
                   "sort" => "int() #排序id",
                   "img" => "string() # 图片地址",
                   "link" => "string() # 链接地址",
                   "url"    => "string() #第三方游戏地址",
                   "openType" => "int() #链接打开方式，0 当前打开， 1 新窗口打开",
                   "game_name" => "string() #游戏名称"
               ]
           ]
   ];


    public function run() {
        return array('hotgame' => [
            /*[
                'cid' => 1,
                'id' => 'mainNav-cp', // 识别
                'name' => '彩票',
                'alias' => 'CP', // 英文别名
                'enabled' => '1', // 是否开启
                'pc_id' => '', // 对应的游戏id
                'sort' => '3', // 排列
                'img' => 'static/images/platform/cp.png', // 图片地址，相对地址
                'link' => '', // 二级菜单链接地址
                'url' => '',
                'openType' => '0',
                'game_name' => 'lottery',
            ],*/
            [
                'cid' => 8,
                'id' => 'mainNav-ebet',
                'name' => 'LEBO视讯厅',
                'alias' => 'ZR',
                'enabled' => '1',
                'pc_id' => '',
                'sort' => '1',
                'img' => 'static/images/platform/lebo.png',
                'link' => '',
                'url' => "/game/third?g=Lebo&c=authorization&loginType=2&token=",
                //  'url'=>'#',
                'openType' => '1',
                'game_name' => 'LEBO',
            ],
            [
                'cid' => 2,
                'id' => 'mainNav-sb',
                'name' => '沙巴体育',
                'alias' => 'SB',
                'enabled' => '1',
                'pc_id' => '13',
                'sort' => '8',
                'img' => 'static/images/platform/sb.png',
                'link' => 'https://lebogame.co/sbapi/login.php',
                // 'url' => 'https://lebogame.co/sbapi/login.php',
                'url' => "/game/third?g=sb&c=lauchGame&loginType=2&token=",
                'openType' => '1',
                'game_name' => '沙巴体育',
            ], // 新窗口打开


            [
                'cid' => 8,
                'id' => 'mainNav-ag',
                'name' => 'AG',
                'alias' => 'AG',
                'enabled' => '1',
                'pc_id' => '8',
                'sort' => '5',
                'img' => 'static/images/platform/ag.png',
                'link' => '',
                'url' => "/game/third?g=Ag&c=lauchGameH5&isMobileUrl=1&token=",
                'openType' => '1',
                'game_name' => 'AG',
            ],


            [
                'cid' => 11,
                'id' => 'mainNav-Pt',
                'name' => 'PT',
                'alias' => 'PT',
                'enabled' => '1',
                'pc_id' => '',
                'sort' => '12',
                'img' => 'static/images/platform/pt.png',
                'url' => "/game/third?g=pt&c=gameList&token=",
                //   'url' => "",
                'openType' => '1',
                'game_name' => 'PT',
            ],
            [
                'cid' => 16,
                'id' => 'mainNav-Fg',
                'name' => 'FG',
                'alias' => 'FG',
                'enabled' => '1',
                'pc_id' => '',
                'sort' => '12',
                'img' => 'static/images/platform/fg.png',
                'url' =>"/game?g=fg&c=gameList&token=",
                //'url' => "",
                'openType' => '1',
                'game_name' => 'FG',
            ],
        ]);
    }
};