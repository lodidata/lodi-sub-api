<?php
namespace Model;
use DB;
class BankUser extends \Illuminate\Database\Eloquent\Model {

    const MAX_CARD_NUM = 5;
    const MAX_CARD_NUM_NEW = 15;     //新改版需求，一个用户最大只能绑定15张银行卡

    protected $table = 'bank_user';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'bank_id',
        'name',
        'fee',
        'card',
        'address',
        'role',
        'state',
    ];

    public static function getRecordsById($id) {
        $data = DB::table('bank_user')
            ->leftjoin('bank', 'bank_user.bank_id', '=', 'bank.id')
            ->where('bank_user.role', 1)
            ->where('state','enabled')
            ->where('bank_user.id', $id)
            ->selectRaw('bank_user.card, bank.code, bank.name, bank.id')
            ->get()
            ->toArray();

        if (!empty($data)) {
            $data = \Utils\Utils::RSAPatch($data);
            $data = current($data);
        }

        return $data;
    }

    /**
     * 获取用户银行卡列表
     * @param $userId
     * @param int $isShow 是否加密
     * @return array
     */
    public static function getRecords($userId, $isShow = 0) {
        $data = DB::table('bank_user')
            ->leftjoin('bank', 'bank_user.bank_id', '=', 'bank.id')
            ->where('bank_user.role', 1)
            ->where('bank_user.user_id', $userId)
            ->selectRaw('bank_user.id, bank_user.name, bank_user.card, bank.name AS bank_name, bank.code AS short_name, bank_user.address AS deposit_bank,  UNIX_TIMESTAMP(bank_user.inserted) AS time,bank_user.updated,bank.shortname,bank_user.state,bank.h5_logo,bank.logo')
            ->get()
            ->toArray();

        if (!empty($data)) {
            $data = \Utils\Utils::RSAPatch($data);
        }
        $newData = [];
        foreach ($data ?? [] as $val) {
            $temp = (array) $val;
            /*if ($temp['name']) {
                $temp['name'] = mb_substr($temp['name'], 0, 1).'*'.mb_substr($temp['name'], 2);
            }*/
            $temp['account'] = $isShow == 0 ? ('**** **** **** '.substr($temp['card'], -4)) : $temp['card'];
            unset($temp['card']);
            $newData[] = $temp;
        }
        return $newData;
    }

    public static function getBancardId($userId) {
        $data = DB::table('bank_user')
            ->where('bank_user.user_id', $userId)
            ->where('bank_user.role', 1)
            ->where('bank_user.state', 'enabled')
            ->value('bank_user.id');

        return $data;
    }
    public static function getDetail($id) {
        $data = DB::table('bank_user')
            ->leftjoin('bank', 'bank_user.bank_id', '=', 'bank.id')
            ->where('bank_user.role', 1)
            ->where('bank_user.id', $id)
            ->selectRaw('bank_user.id, bank_user.name, bank_user.card, bank.name AS bank_name, bank.code AS short_name, bank_user.address AS deposit_bank, UNIX_TIMESTAMP(bank_user.inserted) AS time,bank_user.updated,bank.shortname,bank_user.state,bank.h5_logo,bank.logo')
            ->first();

        if (!empty($data)) {
            $data = \Utils\Utils::RSAPatch($data);
        }
        $newData = [];
        foreach ($data ?? [] as $val) {
            $temp = (array) $val;
            if ($temp['name']) {
                $temp['name'] = mb_substr($temp['name'], 0, 1).'*'.mb_substr($temp['name'], 2);
            }
            $temp['account'] = '**** **** **** '.substr($temp['card'], -4);
            unset($temp['card']);
            $newData[] = $temp;
        }
        return $newData;
    }


}


