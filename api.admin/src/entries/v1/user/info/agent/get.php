<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/6 15:31
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\User\Agent as agentLgoic;

return new class() extends BaseController {
    const TITLE = '查看代理推广资料(人人代理)';
    const DESCRIPTION = '';

    const QUERY = [
        'user_id' => 'int(required) #代理id',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($uid) {
        $agent = new \Logic\User\Agent($this->ci);
        $res['marker_link'] = $agent->generalizeList($uid);

        $info = \Model\UserAgent::where('user_id', $uid)->first()->toArray();
        $default = $agent->rakeBackJuniorDefault();
        //本身返佣范围
        $height_up = $agent->allow('','',(int)$info['uid_agent'],$uid,true)->getData();
        $game = \Model\Admin\GameMenu::where('pid',0)->where('switch','enabled')->get()->toArray();
        $game = array_column($game,NULL,'type');
        $bkge = json_decode($info['bkge_json'],true);
        $junior = json_decode($info['junior_bkge'],true);
        foreach ($default as $key=>$val) {
            //下级返佣及下级返佣范围
            if(!isset($bkge[$key]) || !isset($junior[$key])) {   //说明该大类是后面添加的，
                $update = $agent->synchroUserBkge($uid,$info);
                $bkge = $update['bkge'];
                $junior = $update['junior'];
            }
            if(!isset($game[$key])) continue;
            $tmp['key_desc'] = $game[$key]['name'];
            $tmp['key'] = $key;
            $tmp['value'] = $junior[$key];
            $tmp['desc'] = "% (0%-{$bkge[$key]}%)";
            $res['junior_rake_back'][] = $tmp;
            //本身返佣及返佣范围
            $tmp['key_desc'] = $game[$key]['name'];
            $tmp['key'] = $key;
            $tmp['value'] = $bkge[$key];
            $min = $height_up['MIN_'.$key] ?? 0;
            $max = $height_up['MAX_'.$key] ?? 0;
            $tmp['desc'] = "% ({$min}%-{$max}%)";
            $res['rake_back'][] = $tmp;
        }
        return $res;
    }
};
