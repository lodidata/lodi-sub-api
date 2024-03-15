<?php

use Logic\Define\CacheKey;
use Utils\Www\Action;
return new class extends Action {
    const TITLE = "获取头奖";
    const TAGS = '游戏';
    const QUERY = [
        'game_type' => 'string # 游戏类型 JOKER,JILI,PG,CQ9,JDB,FC,KMQM,PP,SGMK 多个逗号分开'
    ];
    const SCHEMAS = [
        'jackpot' => 'int #奖池金额'
    ];

    public function run() {

        $game_type = $this->request->getParam('game_type','JILI');


        $allow = \Logic\Set\SystemConfig::getModuleSystemConfig('gameCon');

        if(!isset($allow['allow_in']) || $allow['allow_in'] != 1) {
            return $this->lang->set(3014);
        }
        $array = ['JOKER','JILI','PG','CQ9','JDB','FC','KMQM','PP','SGMK'];

        if(!in_array($game_type, $array)){
            return $this->lang->set(3010);
        }

        try {
            $jackpot = $this->redis->get(CacheKey::$perfix['gameJackPot'] . $game_type);
            if(is_null($jackpot)){
                if($game_type == 'JILI'){
                    $jackpot = random_int(800000, 1200000) . random_int(0,100)/100;
                }else{
                    $gameClass = \Logic\GameApi\GameApi::getApi($game_type, 0);
                    $jackpot  = $gameClass->getJackpot();
                    if($jackpot == 0){
                        $jackpot = random_int(800000, 1200000) . random_int(0,100)/100;
                    }
                }

                $this->redis->setex(CacheKey::$perfix['gameJackPot'] . $game_type, 600, $jackpot);
            }
            return $this->lang->set(0, [], ['jackpot' => $jackpot]);

        } catch (\Exception $e) {
            $this->logger->error('jackpot' . $e->getMessage());
            return $this->lang->set(3011);
        }
    }
};