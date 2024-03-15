<?php
namespace Model;
use DB;
class UserDataReview extends \Illuminate\Database\Eloquent\Model {

    const AGREE  = 1;
    const REFUSE = 2;
    const STATUS = [
        '1'  => '通过',
        '2'  => '驳回',
    ];
    protected $table = 'user_data_review';

    public $timestamps = false;

    protected $fillable = [
        'account',              //'用户账号',
        'name',                 //'真实姓名',
        'user_id',              //'用户id',
        'update_content',       //'更新内容',
        'password',             //'登录密码',
        'pin_password',         //'PIN密码',
        'bank_id',              //'银行id',
        'bank_account_name',    //'开户名',
        'bank_card',            //'银行卡号',
        'salt',                 //'银行卡号',
        'account_bank',         //'开户行',
        'remarks',              //'备注',
        'operator_name',        //'创建人',
        'operator_id',          //'创建人ID',
        'created_id',           //'创建人ID',
        'updated',              //'更新时间',
        'created',              //'创建时间',
        'image',                //'图片',
        'type_id',              //'审核类型',
    ];
    const CN_CONTENT = [
        'name'              =>  '真实姓名',
        'password'          =>  '登录密码',
        'pin_password'      =>  'PIN密码',
        'bank_id'           =>  '银行名称',
        'bank_account_name' =>  '开户名',
        'bank_card'         =>  '银行卡号',
        'account_bank'      =>  '开户行',
    ];
}


