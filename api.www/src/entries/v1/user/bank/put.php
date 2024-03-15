<?php

use Logic\GameApi\Common;
use Model\UserLog;
use Model\User;
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "会员中心-会员新增绑定银行卡";
    const TAGS = "银行卡";
    const PARAMS = [
        "bank"          => "int(required) # 银行id",
        "name"          => "string(required) #户名",
        "account"       => "string(required) #银行账号",
        "deposit_bank"  => "string() #开户支行"
    ];
    const SCHEMAS = [
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'name' => V::Name()->setName($this->lang->text("name validate")),
            'bank' => V::intVal()->noWhitespace()->setName($this->lang->text("bank type")),
            //'deposit_bank' => V::Name()->setName($this->lang->text("bank validate")),
            'account' => V::bankAccounts()->setName($this->lang->text("Bank card number")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $userId = $this->auth->getUserId();
        $role = 1;
        $state = 'enabled';
        $cardNo = $this->request->getParam('account');
        //$address = $this->request->getParam('deposit_bank');
        $fee = 0;
        $name = trim($this->request->getParam('name'));
        $bankId = $this->request->getParam('bank');

        //防止重复提交
        $redis_key = "add_user_bank:{$userId}";
        if ($this->redis->incr($redis_key) > 1) {
            return ;
        }

        $this->redis->expire($redis_key, 5);

        if (empty(\Model\Bank::where('id', $bankId)->first())) {
            return $this->lang->set(122);
        }

//        if (\Model\BankUser::where('user_id', $userId)->where('state','!=','delete')->where('card', \Utils\Utils::RSAEncrypt($cardNo))->first()) {
//            return $this->lang->set(120);
//        }
//        $count = \Model\BankUser::where('user_id', $userId)
//            ->where('role', $role)
//            ->where('state', $state)
//            ->count();
//        if ($count >= \Model\BankUser::MAX_CARD_NUM) {
//            return $this->lang->set(121, [\Model\BankUser::MAX_CARD_NUM]);
//        }

        //检测判断：一张银行卡只能绑定一个账号，已经绑定过则提示：“该银行卡已经被其它账号绑定，请先进行解绑”
        if (DB::table("bank_user")->whereRaw('bank_id = ? and card = ? and state != ?', [$bankId, \Utils\Utils::RSAEncrypt($cardNo), "delete"])->count()) {
            return $this->lang->set(1201);
        }

        //获取用户等级信息
        $userData = User::where('id', $userId)->select(['ranting'])->first()->toArray();
        $bankCardLimit = DB::table('user_level')->where('level', $userData['ranting'])->select(['bankcard_sum'])->first();
        $userBindCard = DB::table("bank_user")->where('user_id', $userId)->where('state', 'enabled')->count();
        if($userBindCard >= $bankCardLimit->bankcard_sum) {
            return $this->lang->set(1202, [$bankCardLimit->bankcard_sum]);
        }

        //检测判断：一个账号最多个绑定15张银行卡
        $count = DB::table("bank_user")->whereRaw('user_id = ? and state != ?', [$userId, "delete"])->count();
        if ($count >= \Model\BankUser::MAX_CARD_NUM_NEW) {
            return $this->lang->set(121, [\Model\BankUser::MAX_CARD_NUM_NEW]);
        }

        \Model\BankUser::create([
            'user_id'   => $userId,
            'bank_id'   => $bankId,
            'name' => $name,
            'card'   => Utils\Utils::RSAEncrypt($cardNo),
            //'address'  => $address,
            'fee'      => $fee,
            'role'     => $role,
        ]);

        \Model\Profile::where('user_id', $userId)->whereRaw('name is null')->update(['name' => $name]);
        \Model\SafeCenter::where('user_id', $userId)->update(['id_card' => 1]);

        //首次绑定银行卡才触发活动
        $count = \Model\BankUser::where('user_id', $userId)->count();
        if($count == 1) {
            (new \Logic\Activity\Activity($this->ci))->bindInfo($this->auth->getUserId(), 3);
        }

        $fromWheres = ['ios' => 2, 'android' => 1, 'h5' => 3, 'pc' => 4];

        //写入日志
        UserLog::create([
            'user_id' => $userId,
            'name' => (new Common($this->ci))->getUserInfo($userId)['name'],
            'log_value' => 'h5 user add bank card No:' . $cardNo,
            'log_type' => 11,
            'status' => 1,
            'platform' => $fromWheres[$this->auth->getCurrentPlatform()]
        ]);

        return $this->lang->set(0);
    }
};