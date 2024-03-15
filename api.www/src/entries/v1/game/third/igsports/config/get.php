<?php

use Logic\GameApi\GameApi;
use Model\Funds;
use Utils\Www\Action;

return new class extends Action
{
    const TITLE = "STG游戏配置";
    const TAGS = 'STG游戏';
    const DESCRIPTION = "";
    const QUERY = [
    ];
    const SCHEMAS = [
        'PartnerId' => 'int #代理ID',
        'SPORTHOSTNAME' => 'string #游戏首页地址',
        'timeZone' => 'int #时区 8',
        'lang' => 'string #语言 en',
        'domain' => 'string #使用网站域名'
    ];

    public function run()
    {
        $gameClass = GameApi::getApi('STG', 0);
        $lobby = json_decode($gameClass->config['lobby'], true);

        $back_url = $this->ci->get('settings')['website']['game_home_url'] ?? $_SERVER['HTTP_HOST'];
        return [
                'PartnerId' => $gameClass->config['cagent'],
                'SPORTHOSTNAME' => $lobby['SPORTHOSTNAME'],
                'timeZone' => $lobby['timeZone'],
                'lang' => $gameClass->langs[LANG]?? $gameClass->langs['en-us'],
                'domain' => $back_url,
                'sportPartner' => $lobby['sportPartner'],
        ];
    }

};