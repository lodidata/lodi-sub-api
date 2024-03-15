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
use Logic\Admin\Log;
use Logic\User\Agent as agentLgoic;

return new class() extends BaseController {
    const TITLE = '更新代理退佣率(人人代理)';
    const DESCRIPTION = '';

    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'junior_rake_back'    => [
                'key' => 'value',
            ],
            'rake_back'    => [
                'key' => 'value',
            ],
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id) {

        $this->checkID($id);
        $user = DB::table('user')->find($id);
        if(!$user)
            return $this->lang->set(10014);

        $bkge = $this->request->getParam('rake_back');
        $junior = $this->request->getParam('junior_rake_back');
        $agent = new \Logic\User\Agent($this->ci);
        $info = \Model\UserAgent::where('user_id', $id)->first()->toArray();
        if($bkge) {
            $height_up = $agent->allow('','',(int)$info['uid_agent'],$id,true)->getData();
            foreach ($bkge as $key => $val) {
                if (!isset($height_up['MAX_' . $key]) && !$height_up['MIN_' . $key]) {
                    return $this->lang->set(550);
                }
                if ($val > $height_up['MAX_' . $key] || $val < $height_up['MIN_' . $key]) {
                    return $this->lang->set(552);
                }
            }
            \Model\UserAgent::where('user_id', $id)->update(['bkge_json'=>json_encode($bkge)]);
        }else { //  更新下级返佣范围
            $height_up = json_decode($info['bkge_json'],true);
            foreach ($height_up as $key => $val) {
                if(!isset($junior[$key])) {
                    return $this->lang->set(550);
                }
                if($junior[$key] > $val) {
                    return $this->lang->set(551);
                }
            }
            \Model\UserAgent::where('user_id', $id)->update(['junior_bkge'=>json_encode($junior)]);
        }
        return $this->lang->set(0);
    }
};
