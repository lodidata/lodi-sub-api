<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/6 18:53
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;
use Model\UserDataReview;

return new class() extends BaseController {
    const TITLE       = '管理人员操作资料审核';
    const DESCRIPTION = '用户资料--通过/驳回';
    

    const PARAMS      = [
        'status' => 'int(required) #true 1 or false 2'
    ];
    const STATEs      = [
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = '')
    {
        $this->checkID($id);
        (new BaseValidate([
                'status' =>'require|in:0,1,2',
                'remarks'=>'string |max:500',
            ]
        ))->paramsCheck('',$this->request,$this->response);

        $status = $this->request->getParam('status');
        $user_data_review = UserDataReview::where('id', $id)->first();
        if (!$user_data_review)  return $this->lang->set(10015);
        //审核通过
        if ($status== UserDataReview::AGREE){
            //同步更新关联表数据
            DB::beginTransaction();
            try {
                $user_data = $user_data_review->toArray();
                // 更新bank_user数据表
                $bank_user['name']               = $user_data['bank_account_name'];
                $bank_user['bank_id']            = $user_data['bank_id'];
                $bank_user['card']               = $user_data['bank_card'];
                $bank_user['address']            = $user_data['account_bank'];
                if ($update_bank_user = array_filter($bank_user)){
                    $bank_user_info = \DB::table('bank_user')
                        ->where('user_id',$user_data['user_id'])
                        ->where('state','!=','delete')->limit(1)->get();
                   //存在银行卡信息更新，不存在新增
                    if (isset($bank_user_info[0])){
                        \DB::table('bank_user') ->where('id',$bank_user_info[0]->id)->update($update_bank_user);
                    }else{
                        foreach ($bank_user as $v){
                            if (empty($v)) return createRsponse($this->response, 200, -2, '新增银行卡,需填写卡号,户名,帐号,开户行');
                        }
                        $bank_user['user_id'] = $user_data['user_id'];
                        \DB::table('bank_user')->insert($bank_user);
                    }
                }
                // 更新user数据表
                if ($user_data['password']){
                    $user_sql['password']  = $user_data['password'];
                    $user_sql['salt']      = $user_data['salt'];
                    \DB::table('user') ->where('id',$user_data['user_id'])->update($user_sql);

                }
                // 更新profile数据表
                if ($user_data['name']){
                    $profile['name']  = $user_data['name'];
                    \DB::table('profile') ->where('user_id',$user_data['user_id'])->update($profile);
                }

                //同步更新funds表
                if ($user_data['pin_password']){
                    $funds_sql['password']  = $user_data['pin_password'];
                    $funds_sql['salt']      = $user_data['salt'];
                    \DB::table('funds')->where('id',$user_data['user_id'])->update($funds_sql);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();

                (new Log($this->ci))->create(null, null, Log::MODULE_USER, '会员管理', '审核资料', "修改", 0, "操作id：{$id}");
                return $this->lang->set(-2);
            }
            //更新通过审核状态
            $user_data_review->status           = UserDataReview::AGREE;
            $user_data_review->updated          = date('Y-m-d H:i:s',time());
            $user_data_review->operator_id       = $this->playLoad['uid'];
            $user_data_review->save();
        }else{
            $rejection_reason = $this->request->getParam('rejection_reason');
            if (empty($rejection_reason)) return $this->lang->set(10);
            //更新驳回审核状态
            $user_data_review->status           = UserDataReview::REFUSE;
            $user_data_review->rejection_reason = $rejection_reason;
            $user_data_review->updated          = date('Y-m-d H:i:s',time());
            $user_data_review->operator_id       = $this->playLoad['uid'];
            $user_data_review->save();
        }
        $logs = new \Model\Admin\LogicModel();
        $logs->logs_type = '资料审核/审批';
        $logs->opt_desc = '用户名('.$user_data_review->account.')-操作:'.UserDataReview::STATUS[$status];
        $logs->setTarget(0,$user_data_review->account);
        $logs->log();
        return $this->lang->set(0);
    }
};
