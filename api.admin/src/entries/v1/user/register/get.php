<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/5 14:53
 */

use Logic\Admin\BaseController;
use Logic\User\Agent as agentLgoic;
return new class() extends BaseController{
    const TITLE = '会员列表';
    const DESCRIPTION = '所有会员';
    
    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $res = [];
        /*$default = \Logic\Set\SystemConfig::getModuleSystemConfig('rakeBack');
        unset($default['agent_switch']);
        $game = \Model\Admin\GameMenu::where('pid',0)->get()->toArray();
        $game = array_column($game,NULL,'type');
        foreach ($default as $key=>$val) {
            switch ($key){
                case 'agent_switch':
                    $name = '人人返佣开关';
                    break;
                case 'bkge1':
                    $name = '上级返佣';
                    break;
                case 'bkge2':
                    $name = '上上级返佣';
                    break;
                default:
                    $name =  $game[$key]['name'];
            }
            $tmp['key_desc'] = $name;
            $tmp['key'] = $key;
            $tmp['value'] = 0;
            $tmp['desc'] = "% (0%-{$val}%)";
            $res['rake_back'][] = $tmp;
        }
        return $res;*/
    }
};
