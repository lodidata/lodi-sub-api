<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '用户银行资料';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id'        => 'int(required) #用户id',
        'type'      => 'enum[stat,base,balance,withdraw,bank](required) #获取细分项，可能值：统计 stat，基本信息 base，账户余额 balance，取款稽核 withdraw，银行信息 bank',
        'page'      => 'int()#当前页数',
        'page_size' => 'int() #一页多少条数'
    ];
    
    const STATEs      = [
//        \Las\Utils\ErrorCode::INVALID_VALUE => '无效用户id'
    ];
    const PARAMS      = [];
    const SCHEMAS     = [
        ['map # channel: registe=网站注册, partner=第三方,reserved =保留'],
    ];

    protected $beforeActionList = [
        'verifyToken',
       'authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);

        $rs = $this->cardList($id, 1);
        return $rs;

    }


    /**
     * 读取用户名下银行列表
     *
     * @param int    $userId
     * @param int    $role
     * @param string $state
     * @return array
     */
    public function cardList(int $userId, int $role = null, string $state = null)
    {

        $query = DB::table('bank_user as a')
            ->leftJoin('bank as b','a.bank_id','=','b.id')
            ->select(DB::raw("
            a.id,b.code as code,b.h5_logo,logo,a.inserted as created_time,a.updated as updated_time,a.state,b.shortname,a.address,a.bank_id,b.code as bank_code,a.name as accountname,a.card 
            "))
            ->where('a.user_id',$userId);

        $query = !empty($role) ? $query->where('a.role',$role) : $query;

        $query = !empty($state) ? $query->whereRaw("AND FIND_IN_SET('{$state}',a.state)") : $query;

        $res = $query->orderBy('a.id','desc')->get()->toArray();
        foreach ($res as &$v){
            $v->bank_name = $this->lang->text($v->bank_code);
        }
        unset($v);
        return \Utils\Utils::RSAPatch($res);
    }

};
