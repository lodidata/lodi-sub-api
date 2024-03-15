<?php

namespace Logic\User;

use \lib\exception\BaseException;
use \Model\BankUser;
use DB;
class Bank extends \Logic\Logic {

    /**
     * 添加银行卡
     *
     * @param int    $userId
     * @param int    $bankId
     * @param string $cardNo
     * @param string $address
     * @param string $name
     * @param int    $fee
     * @param int    $role
     * @throws \Exception
     * @return int|bool
     */
    public function addCard($userId, $bankId, $name, $cardNo, $address, $fee = 0, $role = 1)
    {
        if ($this->boundCardNum($userId, $role) >= BankUser::MAX_CARD_NUM) {
            $newResponse = $this->ci->response->withStatus(200)->withJson(['state' => -2, 'message' => $this->lang->text("You have added more than %s cards",[BankUser::MAX_CARD_NUM])]);
            throw new BaseException($this->request,$newResponse);
        }
        $exists = BankUser::where('card',\Utils\Utils::RSAEncrypt($cardNo))->where('state','!=','delete')->where('user_id',$userId)->first();

        if ($exists) {
            $newResponse = $this->ci->response->withStatus(200)->withJson(['state' => -2, 'message' => $this->lang->text("Card number %s already exists!", [$cardNo])]);
            throw new BaseException($this->request,$newResponse);
        }

        DB::beginTransaction();
        try{
            $rs     = BankUser::create( [
                'user_id'   => $userId,
                'bank_id'   => $bankId,
                'name' => $name,
                'card'   => \Utils\Utils::RSAEncrypt($cardNo),
                'address'  => $address,
                'fee'      => $fee,
                'role'     => $role,
            ]);
            if(!$rs){
                $newResponse = $this->response->withStatus(200)->withJson(['state' => -2, 'message' => $this->lang->text("Add failed")]);
                throw new BaseException($this->request,$newResponse);
            }

            $userProfile = DB::table('profile')->where('user_id',$userId)->first();
            if (!$userProfile->name) {

                $rs = DB::table('profile')->where('user_id',$userId)->update(['name'=>$name]);
                if(!$rs){
                    $newResponse = $this->response->withStatus(200)->withJson(['state' => -2, 'message' => $this->lang->text("Failed to change profile")]);
                    throw new BaseException($this->request,$newResponse);
                }
            }

            DB::commit();
        }catch (BaseException $e){

            DB::rollback();
            throw $e;
        }
    }

    /**
     * 判断用户绑定了多少张银行卡
     *
     * @param int $userId
     * @param int $role
     * @return int
     */
    public function boundCardNum($userId, $role = 1, $state = 'enabled')
    {
        return BankUser::where('user_id',$userId)->where('role',$role)->where('state',$state)->count();

    }

    /**
     * @param $userId
     * @param $cardNo
     * @param $role
     * @throws \InvalidArgumentException
     * @return array|bool
     */
    public function deleteCard($userId, $cardId)
    {
        $user_id = (int)$userId;
        $card_id = (int)$cardId;
        $state   = 3;
        $message = $this->lang->text("operation failed");
        if (empty($user_id) || empty($card_id)) {
            return $this->return_status($state, $this->lang->text("User ID or card ID cannot be empty"));
        }
        $driver     = db('core');
        $card_where = [
            'user_id' => $user_id,
            'id'      => $card_id
        ];
        $card_sql   = $this->getHelper()->select('bank_user')->fields('card')->where($card_where)->sql();
        $card_num   = $driver->query($card_sql);
        if (!empty($card_num)) {
            $card_num = current($card_num)['card'];
        }

        if (!empty($card_num)) {
            $card_params = [
                'state' => 'disabled'
            ];
            $card_sql    = $this->getHelper()->update('bank_user')->set($card_params,false)->where($card_where)->sql();
            $result      = $driver->query($card_sql);
            $card_num= RSADecrypt($card_num);
            $card_num    = substr($card_num, -4);
            if ($result >= 0) {
                return $this->return_status(0, $this->lang->text("You have successfully deleted the bank card information with ending number %s!", [$card_num]));
            }

        }

        //
        return $this->return_status($state, $message);
    }


}