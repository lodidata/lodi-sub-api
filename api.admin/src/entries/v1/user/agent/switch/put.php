<?php
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;
use Logic\Set\SystemConfig;
use Model\GameMenu;

return new class() extends BaseController
{
    const TITLE = '代理开关';
    const DESCRIPTION = '代理开关';

    const QUERY = [
        'agent_switch' => 'enum[1,0](required) #是否开启,1 是，0 否',
    ];

    const SCHEMAS = [
    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id)
    {
        $this->checkID($id);

        (new BaseValidate([
            'agent_switch'=>'require|in:0,1',
        ]))->paramsCheck('',$this->request,$this->response);

        $user = \Model\Admin\User::find($id);
        if(!$user)
            return $this->lang->set(10015);
        $agent_switch=strval($this->request->getParam('agent_switch'));
        if($agent_switch == 1){
            $agent = new \Logic\User\Agent($this->ci);
            $profit = $agent->getProfit($id);
            if($profit){
                DB::table('user_agent')->where('user_id',$id)->update(['profit_loss_value'=>$profit]);
            }
        }

        $user->agent_switch = $agent_switch;
        $user->agent_time   = date('Y-m-d H:i:s',time());
        $user->setTarget($id,$user->name);
        $re = $user->save();
        if($re) return $this->lang->set(0);
        return $this->lang->set(-2);
    }



};
