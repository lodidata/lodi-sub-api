<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Logic\Define\CacheKey;

return new class() extends BaseController {
    const TITLE       = '更新盈亏设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data = $this->request->getParams();
        unset($data['s']);
        if(empty($data)){
            return $this->lang->set(10010);
        }
        $redisKey=CacheKey::$perfix['profit_loss_switch'];
        $redisRes=$this->redis->get($redisKey);
        if($redisRes){
            return $this->lang->set(10421);
        }
        $tmp['profit_loss'] = \Logic\Set\SystemConfig::getModuleSystemConfig('profit_loss');

        if($tmp['profit_loss']['fixed_proportion_switch'] && (isset($data['profit_loss']['default_proportion_switch']) && $data['profit_loss']['default_proportion_switch'])){
            return $this->lang->set(10422);
        }
        if(isset($data['profit_loss']['proportion']) && strpos($data['profit_loss']['proportion'], 'HOT') !== false){
            $proportion_arr = json_decode($data['profit_loss']['proportion'],true);
            $proportion_new = [];
            foreach($proportion_arr as $key => $val){
                if($key == 'HOT'){
                    continue;
                }
                $proportion_new[$key] = $val;
            }
            $data['profit_loss']['proportion'] = json_encode($proportion_new);
        }

        $confg = new \Logic\Set\SystemConfig($this->ci);
        $confg->updateSystemConfig($data,$tmp);

        if(isset($data['profit_loss']['default_proportion_switch']) && $data['profit_loss']['default_proportion_switch']){
            \Utils\MQServer::send('profit_loss_switch', [
                'type'=> 'default_proportion_switch'
            ]);
        }

        if(isset($data['profit_loss']['fixed_proportion_switch']) && $data['profit_loss']['fixed_proportion_switch']){
            \Utils\MQServer::send('profit_loss_switch', [
                'type'=> 'fixed_proportion_switch'
            ]);
        }

        if(isset($data['profit_loss']['sub_proportion_switch']) && $data['profit_loss']['sub_proportion_switch']){
            \Utils\MQServer::send('profit_loss_switch', [
                'type'=> 'sub_proportion_switch'
            ]);
        }

        return $this->lang->set(0);
    }
};
