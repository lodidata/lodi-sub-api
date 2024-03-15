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
        $this->checkID($id);

        $message = $this->request->getParams();

        //判断是否有权限修改用户资料
        $rid = intval($this->playLoad['rid']);
        $memberControls = (new Logic\Admin\AdminAuth($this->ci))->getMemberControls($rid);
        if (!$memberControls || !$memberControls['address_book']) {
            return createRsponse($this->response, 401, 10401, '没有权限修改');
        }

        (new BaseValidate([
            'mobile' => 'checkValueByRegex:mobile',
            'qq'     => 'checkValueByRegex:qq',
        ], [
            'mobile' => '手机号码格式错误',
            'qq'     => 'QQ号码格式错误',
        ]))->paramsCheck('', $this->request, $this->response);

        /*================================日志操作代码=================================*/
        $data = DB::table('user')
                  ->select('user.name as name', 'user.telphone_code', 'user.mobile', 'profile.email', 'profile.qq', 'profile.weixin', 'profile.comment')
                  ->leftJoin('profile', 'user_id', '=', 'user.id')
                  ->where('id', $id)
                  ->get()
                  ->first();

        $data = (array)$data;
        /*==================================================================================*/

        if (isset($message['rake_agent'])) {
            $rake_agent = [
                'bkge_game'    => $message['rake_agent']['bkge_game'] ?? 0,
                'bkge_live'    => $message['rake_agent']['bkge_live'] ?? 0,
                'bkge_sport'   => $message['rake_agent']['bkge_sport'] ?? 0,
                'bkge_lottery' => $message['rake_agent']['bkge_lottery'] ?? 0,
            ];

            $allow = (new agentLgoic($this->ci))->allow($rake_agent, '', 0, $id);

            if (!$allow->getState()) {
                return $allow;
            }

            $str = $this->getStr($data, $message, $id, $rake_agent);

            $res = DB::table('user_agent')
                     ->where('user_id', $id)
                     ->update($rake_agent);

            unset($message['rake_agent']);
        }

        /*================================日志操作代码=================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create($id, $data['name'], Log::MODULE_USER, '会员管理', '基本信息', "修改", $sta, $str);
        /*==================================================================================*/

        return $this->updateUserInfo($id, $message);
    }

    public function updateUserInfo($id, array $arr) {
        DB::beginTransaction();

        try {
            if (isset($arr['mobile']) && !empty($arr['mobile'])) {

                $telphone_code = isset($arr['telphone_code']) ? $arr['telphone_code'] : '';
                $mobile = \Utils\Utils::RSAEncrypt($arr['mobile']);

                $result = DB::table('user')
                            ->where('id', $id)
                            ->update(['mobile' => "$mobile", 'telphone_code' => $telphone_code]);
                if ($result === false) {

                    $newResponse = $this->response->withStatus(200)
                                                  ->withJson(['state' => -2, 'message' => '更新user失败',]);
                    throw new BaseException($this->request, $newResponse);
                }
                $result = DB::table('safe_center')
                            ->where('user_id', $id)
                            ->update(['mobile' => 1]);
                if ($result === false) {

                    $newResponse = $this->response->withStatus(200)
                                                  ->withJson(['state' => -2, 'message' => '更新safe_center失败',]);
                    throw new BaseException($this->request, $newResponse);
                }

                unset($arr['telphone_code']);
                unset($arr['mobile']);
            }

            if (isset($arr['telphone_code'])) {
                unset($arr['telphone_code']);
            }

            if (isset($arr['mobile'])) {
                unset($arr['mobile']);
            }

            if (count($arr)) {
                $arr['updated'] = date('Y-m-d H:i:s');

                $result = DB::table('profile')
                            ->where('user_id', $id)
                            ->update(\Utils\Utils::RSAPatch($arr, \Utils\Encrypt::ENCRYPT));

                if ($result === false) {
                    $newResponse = $this->response->withStatus(200)
                                                  ->withJson(['state' => -2, 'message' => '更新profile失败']);

                    throw new BaseException($this->request, $newResponse);
                }
            }

            DB::commit();
        } catch (BaseException $e) {
            //TODO:日志
            DB::rollback();//事务回滚

            throw $e;
        }

        (new \Logic\Admin\Cache\AdminRedis($this->ci))->removeUser($id);

        return $this->lang->set(0);
    }

    public function getStr($data, $message, $id, $rake_agent) {
        $str = '';

        if (isset($message['mobile']) && $message['mobile'] != \Utils\Utils::RSADecrypt($data['mobile'])) {
            $str = $str . "/电话[" . \Utils\Utils::RSADecrypt($data['mobile']) . ']更改为：[' . $message['mobile'] . "]";
        }

        if (isset($message['email']) && $message['email'] != \Utils\Utils::RSADecrypt($data['email'])) {
            $str = $str . "/邮箱[" . \Utils\Utils::RSADecrypt($data['email']) . ']更改为：[' . $message['email'] . "]";
        }

        if (isset($message['qq']) && $message['qq'] != \Utils\Utils::RSADecrypt($data['qq'])) {
            $str = $str . "/QQ[" . \Utils\Utils::RSADecrypt($data['qq']) . ']更改为：[' . $message['qq'] . "]";
        }

        if (isset($message['weixin']) && $message['weixin'] != \Utils\Utils::RSADecrypt($data['weixin'])) {
            $str = $str . "/微信[" . \Utils\Utils::RSADecrypt($data['weixin']) . ']更改为：[' . $message['weixin'] . "]";
        }

        if (isset($message['comment']) && $message['comment'] != $data['comment']) {
            $str = $str . "/备注[" . $data['comment'] . ']更改为：[' . $message['comment'] . "]";
        }

        if (isset($message['telphone_code']) && $message['telphone_code'] != $data['telphone_code']) {
            $str = $str . "/区号[" . $data['telphone_code'] . ']更改为：[' . $message['telphone_code'] . "]";
        }

        $user_agent = DB::table('user_agent')
                        ->select('bkge_game', 'bkge_live', 'bkge_sport', 'bkge_lottery')
                        ->where('user_id', '=', $id)
                        ->get()
                        ->first();

        $user_agent = (array)$user_agent;

        $rake_agent_name = [
            'bkge_game'    => "电子返佣",
            'bkge_live'    => "视讯返佣",
            'bkge_sport'   => "体育返佣",
            'bkge_lottery' => "彩票返佣",
        ];

        foreach ($user_agent as $key => $item) {
            if ($item != $rake_agent[$key]) {
                $str = $str . $rake_agent_name[$key] . "[" . ($user_agent[$key] / 100) . "%]更改为：[" . ($rake_agent[$key] / 100) . "%]/";
            }
        }

        return $str;
    }
};
