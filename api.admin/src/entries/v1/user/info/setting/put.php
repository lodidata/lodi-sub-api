<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/16 14:11
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;
use Logic\Auth\Auth as authLogic;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '修改会员信息';
    const DESCRIPTION = '修改信息，停用，封号等';
    
    const QUERY = [
        'id' => 'int #用户id'
    ];
    
    const PARAMS = [
        'ids' => 'string() #用户ids批量操作，例(1,2,3,4)',
        "online" => "string() #是否在线 0 否，1 是",
        "auth_status" => "json() # 可能值：禁止提款/禁止优惠/禁止返水/禁止返佣/维护可进，对应：['refuse_withdraw','refuse_sale','refuse_rebate','refuse_bkge','maintaining_login']，设置 1 是，0 否。eg:{'refuse_withdraw':0,'refuse_sale':1}",
        "limit_status" => "int() # 是否自我限制，1 是，0 否",
        "limit_video" => "int() #视讯盈利限制",
        "limit_lottery" => 'int() #彩票盈利限制',
        "state" => "int() #账号状态，0停用,1(启用或者解封，展示只展示启用),2黑名单,3删除,4封号"
    ];
    const SCHEMAS = [];
//前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];


    public function run($id)
    {
        $this->checkID($id);

        (new BaseValidate([
            'state' => 'in:0,1,2,3,4',//账户状态(0禁用1启用2黑名单3删除4封号)
            'online' => 'in:0,1',
        ]))->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();
        if (empty($params)) {
            return $this->lang->set(10010);
        }
        //判断是否有该用户
        $data = DB::table('user')->find($id);
        if (!$data) {
            return $this->lang->set(10014);
        }

        $statusArr = [];
        if (isset($params['auth_status']) && is_array($params['auth_status'])) {
            $statusArr = [];
            foreach ($params['auth_status'] as $param => $status) {
                if ($status) {
                    $statusArr[] = $param;

                } else {
                    if ($param == 'refuse_withdraw')
                        DB::table('user')->where('id', $id)->update(['withdraw_error_times' => 0]);
                }


            }
        }

        if (isset($params['state']) && $params['state'] == 2) {
            $this->redis->del(\Logic\Define\CacheKey::$perfix['pwdErrorLimit'] . '_' . $id);
        }

        $res =true;
        if (isset($params['ids'])) {
            $ids = explode(',', $params['ids']);
            foreach ($ids as $v) {
                $res = $this->update($v, $params,$statusArr);
            }
        } else {
            $res = $this->update($id, $params,$statusArr);
        }
        if($res!==false){
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }

    /**
     * 更新用户信息
     *
     * @param number $id
     * @param array $condtion
     * @return boolean|mixed
     */
    protected function update($id = 0, $condtion = [],$statusArr=[])
    {

        if (isset($condtion['online']) && $condtion['online'] == 0 || isset($condtion['state']) && $condtion['state'] == 0 || isset($condtion['state']) && $condtion['state'] == 4) {

            (new authLogic($this->ci))->logout($id);
        }

        $condtion['id'] = $id;
        $condtion = \Utils\Utils::RSAPatch($condtion, \Utils\Encrypt::ENCRYPT);
        unset($condtion['id']);
        unset($condtion['s']);
        if (isset($condtion['state']) && $condtion['state'] == 0 && !isset($condtion['forbidden_des'])) {
            return $this->lang->set(10013, ['missing forbidden_des']);
        }

        if (empty($condtion)) {
            return $this->lang->set(10010);

        }
        $th = \Model\Admin\User::find($id);
        foreach ($condtion as $key=>$val) {
            if($key=='auth_status' ){
                if($statusArr){
                    $th->$key = implode(',',$statusArr);
                }else{
                    $th->$key ='';
                }
            }else{
                $th->$key = $val;
            }
        }
        $th->setTarget($id,$th->name);
        return $th->save();
    }


};
