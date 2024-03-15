<?php

namespace Logic\Wallet;

use Logic\GameApi\Common;
use Model\User;
use Exception;
use DB;

/**
 * 打码量模块
 */
class Dml extends \Logic\Logic
{

    /**
     * 出款稽核
     *
     * @param int $userId 用户ID
     * @param null|array $funds 主钱包数据
     *
     * @return array [
     *   'factCode' => 'int #实际打码量',
     *   'codes'    => 'int #应有打码量',
     *   'canMoney' => 'int #可提余额',
     *   'balance'  => 'int #钱包余额',
     * ]
     */
    public function checkDml($userId, $funds = null)
    {
        try {
            $balance = $funds == null ? User::getUserTotalMoney($userId)['lottery'] : $funds['balance'];

            $dmlData = DB::table('user_data')
                ->where('user_id', $userId)
                ->first(['total_bet', 'total_require_bet', 'free_money']);

            if ($dmlData) {
                return [
                    'factCode' => $dmlData->total_bet,
                    'codes' => $dmlData->total_require_bet,
                    'canMoney' => $dmlData->free_money,
                    'balance' => $balance,
                ];
            }
        } catch (Exception $e) {

        }

        return [
            'factCode' => 0,
            'codes' => 0,
            'canMoney' => 0,
            'balance' => 0,
        ];
    }

    /*
     * @param userId  用户ID
     * @param money 变动金额
     * @param opt  操作 1变动实际打码量 2变动应有打码量 3减少可提余额
     */
    public function getUserDmlData($userId, $money = 0, $opt = null, $funds = null)
    {
        $userDmlData = $this->checkDml($userId, $funds);

        $dmlData = new \stdClass();

        $dmlData->total_bet = $userDmlData['factCode'];
        $dmlData->total_require_bet = $userDmlData['codes'];
        $dmlData->free_money = $userDmlData['canMoney'];
        $dmlData->balance = $userDmlData['balance'];

        if ($opt == 1) {
            $dmlData->total_bet += $money;
        } else if ($opt == 2) {
            $dmlData->total_require_bet += $money;
        } else if ($opt == 3) {
            $dmlData->free_money -= $money;
        }

        if ($dmlData->free_money <= 0) {
            $dmlData->free_money = 0;
        }

        if ($dmlData->total_bet <= 0) {
            $dmlData->total_bet = 0;
        }

        if ($dmlData->total_require_bet <= 0) {
            $dmlData->total_require_bet = 0;
        }
        $wid = (new Common($this->ci))->getUserInfo($userId)['wallet_id'];
        //$wid = \Model\User::where('id',$userId)->value('wallet_id');
        $child_balance = \Model\FundsChild::where('pid',$wid)->sum('balance');
        $balance = $dmlData->balance + $child_balance;
        //当实际打码量大于应有打码量时，可提余额为钱包金额
        if ($dmlData->total_bet >= $dmlData->total_require_bet) {
            $dmlData->free_money = $balance;

            $update = [
                'free_money' => $dmlData->free_money,
                'total_bet' => 0,
                'total_require_bet' => 0,
            ];
        } else {
            $update = [
                'free_money' => $dmlData->free_money,
                'total_bet' => $dmlData->total_bet,
                'total_require_bet' => $dmlData->total_require_bet,
            ];
        }
        if ($opt && $dmlData->free_money > $balance) {
            $update['free_money'] = $dmlData->free_money = $balance;
        }

        //更新完相应信息拿 最新的数据
        User::updateBetData((int)$userId, $update);

        return $dmlData;
    }

    /*
     * 只变动打码量时候的流水记录
     * @param userId  用户ID
     * @param order_number  订单号
     * @param money  变动金额
     * @param opt   操作 1 变动实际打码量   2变动应有打码量  3可提余额
     * @param order_type  操作类型
     */
    public function dmlDealLog(int $user_id, string $order_number, int $money, int $opt = 1, int $order_type = 0)
    {
        $deal_type = $order_type > 10 ? $order_type : 400 + $order_type;
        //$user = User::where('id', $user_id)->first();
        $user = (new Common($this->ci))->getUserInfo($user_id);
        $funds = \Model\Funds::where('id', $user['wallet_id'])->first();
        if ($user && $funds) {
            $dml = $this->getUserDmlData($user_id, (int)$money, $opt, $funds);
            \Model\FundsDealLog::create([
                "user_id" => $user['id'],
                "user_type" => 1,
                "username" => $user['name'],
                "order_number" => $order_number,
                "deal_type" => $deal_type,
                "deal_category" => 1,
                "deal_money" => $money,
                "withdraw_bet" => $opt == 3 ? 0 : $money,
                "free_money" => $dml->free_money,
                "total_require_bet" => $dml->total_require_bet,
                "total_bet" => $dml->total_bet,
                "balance" => intval($funds['balance']),
                "memo" => $this->lang->text("Order").'：' . $order_number . $this->lang->text("Coding flow"),
                "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
            ]);
        }
    }

    /**
     * 下注加打码量
     *
     * @param $userId
     * @param $total_require_bet
     * @return int
     */
    public function updateBetData($userId, $total_require_bet)
    {
        return DB::table('user_data')
            ->where('user_id', $userId)->update([
                'total_require_bet' => DB::raw("total_require_bet + {$total_require_bet}")
            ]);
    }
}