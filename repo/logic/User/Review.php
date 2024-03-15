<?php

namespace Logic\User;

use \lib\exception\BaseException;
use \Model\UserDataReview;
class Review extends \Logic\Logic {

    /**
     * 新增用户资料
     *
     * @param array    $params
     * @throws \Exception
     * @return string
     */
    public function addReview($params)
    {
        $user_id =  \Model\User::getAccountById($params['account']);
        if (!$user_id) {
            $newResponse = $this->ci->response->withStatus(200)->withJson(['state' => -2, 'message' => $this->lang->text("account %s non-exists!", [$params['account']])]);
            throw new BaseException($this->request,$newResponse);
        }
        //去除数组空元素
        $update_content= array_filter($params);
        unset($update_content['s'],$update_content['account']);
        if (empty($update_content))     return createRsponse($this->response, 200, -2, '请至少填写一项');
        $salt           = \Model\User::getGenerateChar(6);

        if(count($params['image']) > 6){
            return createRsponse($this->response, 200, -2, '最多支持6张图片');
        }

        try{
            $rs  = UserDataReview::create([
                'account'            => $params['account'],
                'update_content'     => json_encode(array_keys($update_content)),
                'user_id'            => $user_id,
                'salt'               => $salt,
                'password'           => !empty($params['password']) ? \Model\User::getPasword($params['password'], $salt): '',
                'name'               => $params['name'] ?? "",
                'pin_password'       => !empty($params['pin_password']) ? \Model\User::getPasword($params['pin_password'], $salt) : '',
                'bank_id'            => $params['bank_id'] ?: 0,            //'银行名称',
                'bank_account_name'  => $params['bank_account_name'],    //'开户名',
                'bank_card'          => \Utils\Utils::RSAEncrypt($params['bank_card']) ?? "",            //'银行卡号',
                'account_bank'       => $params['account_bank'] ?? "",         //'开户行',
                'remarks'            => $params['remarks'] ?? "",         //'开户行',
                'created_id'         => $params['uid'],                  //发起人,
                'updated'            => date('Y-m-d H:i:s',time()),              //'更新时间',
                'created'            => date('Y-m-d H:i:s',time()),              //'创建时间',
                'image'              => json_encode(replaceImageUrl($params['image'])),
                'type_id'            => $params['type_id']
            ]);

            if(!$rs){
                $newResponse = $this->response->withStatus(200)->withJson(['state' => -2, 'message' => $this->lang->text("Add failed")]);
                throw new BaseException($this->request,$newResponse);
            }
        }catch (BaseException $e){
            throw $e;
        }
    }

    /**
     * 获取用户资料
     *
     * @param array    $params
     * @throws \Exception
     * @return string
     */
    public function queryReview($params)
    {
        $user_id =  \Model\User::getAccountById($params['account']);
        if (!$user_id) {
            $newResponse = $this->ci->response->withStatus(200)->withJson(['state' => -2, 'message' => "该会员账号错误"]);
            throw new BaseException($this->request,$newResponse);
        }

        //获取银行卡信息
        $bankUserInfo = \DB::table('bank_user')
                            ->where('user_id', $user_id)
                            ->where('state','!=','delete')
                            ->limit(1)
                            ->get();

        //获取登录密码
        $userData = \DB::table('user')->where('id', $user_id)->select(['salt', 'password'])->get()->toArray();

        //获取用户真实姓名
        $profileData = \DB::table('profile')->where('user_id', $user_id)->select(['name'])->get()->toArray();

        //获取用户pin密码
        $fundsData = \DB::table('funds')->where('id', $user_id)->select(['salt', 'password'])->get()->toArray();

        $arr = [
            'password' => $userData[0]->password,
            'pin_password' => $fundsData[0]->password,
            'bank_account_name' => $bankUserInfo[0]->name,   //开户名
            'bank_id' => $bankUserInfo[0]->bank_id,
            'bank_card' => $bankUserInfo[0]->card,           //银行卡号
            'account_bank' => $bankUserInfo[0]->address,     //开户行
            'name' => $profileData[0]->name                  //真实姓名
        ];

        return $arr;
    }

}