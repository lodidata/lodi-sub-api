<?php

use Logic\Admin\Log;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController
{
    const TITLE = '更换渠道信息';

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($user_id = '')
    {
        $this->checkID($user_id);

        (new BaseValidate(
            [
                'number' => 'require',
            ],
            [],
            ['number'=>'渠道ID']
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

        //当前用户渠道信息
        $user = (array)\DB::table('user')->where('id', $user_id)->first(['channel_id']);

        //判断渠道信息是否存在
        $newChannel = (array)\DB::table('channel_management')->where('number',$params['number'])->first(['id']);
        if(empty($newChannel['id'])){
            return $this->lang->set(11101);
        }

        try {
            \DB::beginTransaction();

            //修改当前等级
            DB::table('user')->where('id',$user_id)->update([
                'channel_id' => $params['number']
            ]);

            DB::commit();
            /*============================日志操作代码================================*/
            $uname = DB::table('user')
                        ->where('id', '=', $user_id)
                        ->value('name');

            (new Log($this->ci))->create($user_id, $uname, Log::MODULE_USER, '会员管理', '会员管理/账号转移/渠道号转移', '更换渠道号', 1, '更换渠道 ['.$user['channel_id'].']更改为['. $params['number'].']');
            /*============================================================*/
            return $this->lang->set(0);
        }catch (Exception $e){
            print_r($e->getMessage());
            DB::rollback();
            return $this->lang->set(-2);
        }
    }
};