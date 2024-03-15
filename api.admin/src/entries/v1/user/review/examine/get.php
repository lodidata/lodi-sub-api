<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/15 15:25
 */

use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '资料审核';
    const DESCRIPTION = '';

    const QUERY       = [
        'id' => 'int',
    ];

    const PARAMS      = [
        "id"                 => "1",
        "account"            => "string #用户账号",
        "password"           => "string #密码",
        "pin_password"       => "pin密码 #钱包密码",
        'bank_name'          => 'string #银行名称',
        "bank_account_name"  => "string #开户名",
        "bank_card"          => "string #银行卡号",
        "account_bank"       => "string #开户行",
        "status"             => "tinyint #审核状态,0 审核中，1 通过，2 拒绝",
        "created"            => "datetime #eg:2017-08-31 02:56:26",
        "updated"            => "datetime #eg:2017-08-31 03:03:32",
        "image"              => "json #审核图片",
        "type_id"            => "tinyint #审核类型"
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
        $field =       "udr.id,udr.name,udr.account,udr.account_bank,udr.bank_id,udr.password,udr.remarks,
                        udr.pin_password,udr.bank_card,udr.bank_account_name,bank.name as bank_name, udr.image";
        $user_data_review = DB::table('user_data_review as udr')
            ->leftJoin('bank','udr.bank_id','=','bank.id')
            ->selectRaw($field)
            ->where('udr.id',$id)
            ->first();
        $user_data_review = json_decode(json_encode($user_data_review),TRUE);
        if(!$user_data_review){
            return $this->lang->set(10014);
        }
        $field = 'udr.remarks,';
        //过滤未修改字段
        if ($user_data_review['password'])              $field .= 'user.password,';
        if ($user_data_review['name'])                  $field .= 'profile.name,';
        if ($user_data_review['pin_password'])          $field .= 'funds.password as pin_password,';
        if ($user_data_review['bank_card'])             $field .= 'bank_user.card,';
        if ($user_data_review['account_bank'])          $field .= 'bank_user.address,';
        if ($user_data_review['bank_id'])               $field .= 'bank.name as bank_name,';
        if ($user_data_review['bank_account_name'])     $field .= 'bank_user.name as bank_account_name';
        $field= rtrim($field,',');
        $old_data =
            DB::table('user_data_review as udr')
                ->leftJoin('user','user.id','=','udr.user_id')
                ->leftJoin('bank_user','user.id','=','bank_user.user_id')
                ->leftJoin('funds','user.id','=','funds.id')
                ->leftJoin('bank','bank_user.bank_id','=','bank.id')
                ->leftJoin('profile','udr.user_id','=','profile.user_id')
                ->selectRaw($field)
                ->where('udr.id',$id)
                ->first();
        $user_data_review['bank_card'] = \Utils\Utils::RSADecrypt($user_data_review['bank_card']);
        $user_data_review['image'] = json_decode(replaceImageUrl($user_data_review['image']), TRUE) ?? [];
        $data['old_user_data']  = $old_data;
        $data['news_user_data'] = array_filter($user_data_review);

        $typeData = DB::table('user_data_review as udr')
                        ->selectRaw("udr.type_id")
                        ->where('udr.id',$id)
                        ->first();
        $data['type_id'] = $typeData->type_id;

        return $this->lang->set(0,[],$data);
    }
};
