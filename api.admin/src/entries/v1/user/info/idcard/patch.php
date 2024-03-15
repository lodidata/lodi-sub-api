<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/15 18:08
 */

use Logic\Admin\BaseController;
use lib\exception\BaseException;
use Logic\Admin\Log;
use Logic\User\Safety as safetyLogic;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '修改用户身份证';
    const DESCRIPTION = '会员管理--给用户一个新等级';
    const HINT        = '示例：user/info/idcard';
    const QUERY       = [];
    
    const PARAMS      = [
        'list' => 'rows(required) #格式：[用户id,用户真实姓名,用户身份证]。eg: [[1,"test",12],[2,"test2",123]]'
    ];
    const SCHEMAS     = [
        [
                'success' => 'rows #数组，成功的uid',
                'fail'    => 'rows #数组，失败的uid'
        ]
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        //判断是否有权限修改用户信息
        $rid            = $this->playLoad['rid'];
        $memberControls = (new Logic\Admin\AdminAuth($this->ci))->getMemberControls($rid);
        if ($memberControls) {
            $privileges = $memberControls['true_name'];
            if (!$privileges) {
                return $this->lang->set(10401);
            }
        }
        $post     = $this->request->getParams();
        if(!isset($post['list']) || empty($post['list'])){
            return $this->lang->set(10010);
        }
        foreach ($post['list'] as $item) {
            $this->checkUserInfo($item);//格式校验
            $uid  = $item[0];
            $realname  = $item[1];
            $idcard  = $item[2];

            try{
                $rs = $this->save_idcard($uid, $realname, $idcard);
            }  catch (\Exception $e) {
                //TODO:循环更新失败写日志
            }

            return $rs;

        }

    }

    /**
     * 保存真实姓名和身份证号码
     *
     * @param int    $user_id
     *            用户id
     * @param string $name
     *            真实姓名
     * @param string $idcard
     *            身份证号码
     */
    public function save_idcard($user_id, $name, $idcard) {
        if (empty(trim($idcard))) {
            return createRsponse($this->response,200,15,'身份证号码不能为空!');
        }
        // 为用户创建安全中心数据
        $create_safety = (new safetyLogic($this->ci))->getList($user_id);
//        if ($create_safety['state'] > 0) {
//            return $create_safety;
//        }

//        $driver = $this->module->db('core');
        $user_params = [
            'idcard' => $idcard,
            'name' => $name,
        ];

        $user_params = \Utils\Utils::RSAPatch($user_params, \Utils\Encrypt::ENCRYPT);

        DB::beginTransaction();

        $user_info=DB::table('user')
            ->find($user_id);

        $profile_info=DB::table('profile')
            ->where('user_id',$user_id)
            ->get()
            ->first();

        try{
            $result = DB::table('profile')->where('user_id',$user_id)->update($user_params);
            // 修改安全中心状态 设置身份验证已设置

            $status = DB::table('safe_center')->where('user_id',$user_id)->where('user_type',1)->update(['id_card'=>1]);

            DB::commit();

            /*================================日志操作代码=================================*/


            (new Log($this->ci))->create( $user_id, $user_info->name, Log::MODULE_USER, '会员管理', '基本信息', "修改", 1, "真实姓名：[{$profile_info->name}]更改为[$name]/身份证：[".\Utils\Utils::RSADecrypt($profile_info->idcard)."]更改为[$idcard]");
            /*==================================================================================*/
            return $this->lang->set(0);

        }  catch (\Exception $e) {

            DB::rollback();//事务回滚
            return $this->lang->set(-2);
        }

    }

    protected function checkUserInfo($userInfo=[]){

        if(! DB::table('user')->find($userInfo[0])){

            $newResponse = createRsponse($this->response,200,10013,'用户不存在！');
            throw new BaseException($this->request,$newResponse);
        }


        if(!preg_match('/^[\x{4e00}-\x{9fa5}·s]{2,20}$/u', $userInfo[1])){
            $newResponse = createRsponse($this->response,200,10013,$userInfo[1].'姓名格式错误！');
            throw new BaseException($this->request,$newResponse);
        }

        if(!preg_match("/^[1-9]\\d{5}(\\d{2}|\\d{4})(0[1-9]|1[0-2])(0[1-9]|[1,2][0-9]|3[0-1])(\\d{3}|\\d{3}[0-9,X,x])$/",$userInfo[2])){
            $newResponse = createRsponse($this->response,200,10013,$userInfo[2].'身份证格式错误！');
            throw new BaseException($this->request,$newResponse);
        }

        return true;

    }

};
