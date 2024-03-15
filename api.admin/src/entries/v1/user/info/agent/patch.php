<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/15 18:05
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Model\User as userModel;
return new class() extends BaseController
{
    const TITLE       = '修改用户所属代理';
    const DESCRIPTION = '会员管理';
    
    const QUERY       = [
        'id' => 'int(required) #会员id',
    ];
    
    const PARAMS      = [
        'agent' => 'string(required) #新代理名称',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        (new BaseValidate([
            'id'=>'require|isPositiveInteger',
            'agent'=>'require|chsDash|length:5,16'
        ]))->paramsCheck('',$this->request,$this->response);

        $params  = $this->request->getParams();

        $user     = $user = DB::table('user')->find($params['id']);
        if (!$user) {
            return createRsponse($this->response,200,16,'用户不存在');
        }
        $user = (array) $user;
        // 查看代理是否存在
        $agent_id = (new userModel())::getAgentIdByName($params['agent'], true);;
        if (!$agent_id) {
            return createRsponse($this->response,200,16,'代理'.$params['agent'].'不存在');
        }

        if($user['agent_id'] == $agent_id){
            return createRsponse($this->response,200,-2,'代理没有变化');
        }

        DB::beginTransaction();

        try{
            DB::table('user')->where('id',$params['id'])->update(['agent_id'=>$agent_id]);
            DB::table('user')->where('id',$agent_id)->increment('play_num');
            DB::table('user')->where('id',$user['agent_id'])->decrement('play_num');

            DB::commit();
        } catch (\Exception $e){

            DB::rollback();
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }

};
