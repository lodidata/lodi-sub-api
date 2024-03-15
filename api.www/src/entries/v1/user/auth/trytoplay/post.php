<?php
/**
 * 试玩用户注册
 * @author Taylor 2019-01-05
 */
use Utils\Www\Action;
use Model\FundsTrialDealLog;

return new class extends Action {
    const HIDDEN = true;
    const TITLE = "试玩用户注册";
    const DESCRIPTION = "试玩用户注册(
    # 充值            /wallet/recharge
    # 提现            /wallet/withdraw
    # 修改密码        /user/password
    # 额度转换        /user/exchange)
    客户端要主动隐藏或不调用这些接口";
    const TAGS = "登录注册";
    const PARAMS = [
        "verify" => "string() #验证码",
        "token"  => "string() #验证码token",
    ];
    const SCHEMAS = [
        "token" => "string #登录token",
        "trytoplay" => "int # 1试玩用户标识",
        "message" => "string #提示信息",
        'http_code'=> "int #HTTP请求状态，200表示正常，400表示错误的请求",
    ];

    public function run() {
        $verify = $this->request->getParam('verify');
        $token = $this->request->getParam('token');

        $global = \Logic\Set\SystemConfig::getModuleSystemConfig('register');

        // 校验试玩开关
        if (!$global['free_try_play']) {
            return $this->lang->set(24);
        }

        $user = new \Logic\User\User($this->ci);
        $res = $user->registerByTryToPlay($token, $verify);
        if ($res instanceof \Logic\Define\Lang && !$res->getState()) {
            // 自动登录
            $res = $this->auth->loginTrialById($user->getUserId());

            if ($res->getState()) {
                return $res;
            }

            //试玩用户充值
            (new \Logic\Recharge\Recharge($this->ci))->tzTrialHandRecharge(
                $userId = $user->getUserId(),
                $amount = 200000,
                $withdraw_bet = 0,
                $memo = $this->lang->text('trial user automatically added'),
                $currentUserId = 1,
                FundsTrialDealLog::TYPE_ADDMONEY_MANUAL
            );

            return $this->lang->set(138, [], $this->lang->getData() + ['trytoplay' => 1]);
        }

        return $res;
    }
};
