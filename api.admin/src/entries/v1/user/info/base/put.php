<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/7 13:04
 */

use Logic\Admin\BaseController;
use lib\exception\BaseException;
use Logic\Admin\Log;
use Logic\User\Agent as agentLgoic;
use lib\validate\BaseValidate;

return new class() extends BaseController {
    const TITLE = '修改会员资料';
    const DESCRIPTION = '会员管理--只能修改一部分';
    
    const QUERY = [
        'id' => 'int(required) #会员id',
    ];
    
    const PARAMS = [
        'address' => 'string() #地址',
        'mobile'  => 'string() #手机',
        'qq'      => 'string()',
        'weixin'  => 'string()',
        'comment' => 'string()',
    ];
    const STATEs = [
//        \Las\Utils\ErrorCode::NO_PERMISSION => '无权限操作'
    ];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id) {

        //判断是否有权限修改用户资料
        $rid = intval($this->playLoad['rid']);
        $memberControls = (new Logic\Admin\AdminAuth($this->ci))->getMemberControls($rid);
        if (!$memberControls || !$memberControls['address_book']) {
            return createRsponse($this->response, 401, 10401, '没有权限修改');
        }

        $this->checkID($id);

        (new BaseValidate(
            [
                'username'=>'max:64',
                'truename'=>'max:30|min:2',
                'qq'=>'max:15',
//                'mobile'=>'max:15',

            ]
        ))->paramsCheck('',$this->request,$this->response);

        $nickname = $this->request->getParsedBodyParam('nickname');  //昵称
        $truename = $this->request->getParsedBodyParam('truename','');
        $qq       = $this->request->getParsedBodyParam('qq','');
        $mobile   = $this->request->getParsedBodyParam('mobile','');
        $wechat   = $this->request->getParsedBodyParam('wechat','');
        $email    = $this->request->getParsedBodyParam('email','');
        $birth    = $this->request->getParsedBodyParam('birth','');
        $idcard   = $this->request->getParsedBodyParam('idcard','');
        $address  = $this->request->getParsedBodyParam('address','');

        if (mb_strlen($mobile) < 8) {
            return $this->lang->set(11029);
        }elseif (mb_strlen($mobile) > 15) {
            return $this->lang->set(11030);
        }elseif (!is_numeric($mobile)) {
            return $this->lang->set(11028);
        }

        $user = \Model\Admin\User::find($id);
        if(!$user)
            return $this->lang->set(10015);
        $tmp = $profile = \Model\Admin\Profile::find($id);
        try{
            DB::beginTransaction();
            $mobile && $user->mobile = \Utils\Utils::RSAEncrypt($mobile);
            $wechat && $user->wechat = \Utils\Utils::RSAEncrypt($wechat);
            $email  && $user->email = \Utils\Utils::RSAEncrypt($email);
            $user->setTarget($id,$user->name);
            $user->save();

            $profile->nickname = $nickname;
            $profile->name = $truename;
            //$profile->name = \Utils\Utils::matchChinese($truename);
            $profile->address = $address;
            $mobile && $profile->mobile = \Utils\Utils::RSAEncrypt($mobile);;
            $birth  && $profile->birth  = $birth;
            $email  && $profile->email  = Utils\Utils::RSAEncrypt($email);
            $wechat && $profile->weixin = Utils\Utils::RSAEncrypt($wechat);
            $qq     && $profile->qq = \Utils\Utils::RSAEncrypt($qq);
            $idcard && $profile->idcard = \Utils\Utils::RSAEncrypt($idcard);
            $profile->save();
            $isBind = \Model\SafeCenter::where('user_id', $id)->first();
            if($mobile && !$isBind->mobile) {   //绑定手机
                \Model\SafeCenter::where('user_id', $id)
                    ->update(['mobile' => 1]);
                $activity = new \Logic\Activity\Activity($this->ci);
                $activity->bindInfo($id, 1);
            }
            if($email  && !$isBind->email) { //绑定邮箱
                \Model\SafeCenter::where('user_id', $id)->update(['email' => 1]);
                $activity = new \Logic\Activity\Activity($this->ci);
                $activity->bindInfo($id, 2);
            }
            if($idcard && !$isBind->id_card) { //绑定身份证
                \Model\SafeCenter::where('user_id', $id)->update(['id_card' => 1]);
                if(empty(DB::table('user')->where('id',$id)->whereRaw("find_in_set('refuse_sale',auth_status)")->get()->toArray()))
                    (new \Logic\Activity\Activity($this->ci))->bindIdCard($id);// 执行绑定身份证活动
            }
            DB::commit();
            return $this->lang->set(0);
        }catch (\Exception $e){
            DB::rollback();
            throw $e;
        }

    }
};
