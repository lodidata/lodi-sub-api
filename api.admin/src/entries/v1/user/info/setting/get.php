<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/5/12 9:59
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '获取会员设置';
    const DESCRIPTION = "map #
        \"member_id\": 1,
        \"role\":1,
        \"is_unbind\":1,
        \"online\": \"0\", 是否在线
        \"auth_status\": \"refuse_withdraw,refuse_sale,refuse_rebate,refuse_bkge\", 禁止提款/禁止优惠/禁止返水/禁止返佣
        \"limit_status\": \"1\", 是否自我限制
        \"limit_video\": \"2000\", 视讯盈利限制
        \"limit_lottery\": null, 彩票盈利限制
        \"state\": \"1\" 账号状态
        ";
    
    const QUERY       = [
        'id' => 'int #用户id'
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];


    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id='')
    {
        $this->checkID($id);

        $rs = DB::table('user as u')

            ->select(DB::raw("u.id,u.`name`,u.online,
            ifnull(FIND_IN_SET('refuse_withdraw',auth_status) > 0,0) AS refuse_withdraw,
            ifnull(FIND_IN_SET('refuse_sale',auth_status) > 0,0) AS refuse_sale,
            ifnull(FIND_IN_SET('refuse_rebate',auth_status) > 0,0) AS refuse_rebate,
            ifnull(FIND_IN_SET('refuse_bkge',auth_status) > 0,0) AS refuse_bkge,
            ifnull(limit_status,0) as limit_status,ifnull(limit_video,0) as limit_video,
            ifnull(limit_lottery,0) as limit_lottery,u.state"))
            ->where('u.id',$id)
            ->get()->toArray();

        if (!$rs) {
            return [];
        }
        $rs = \Utils\Utils::RSAPatch($rs);
        // 会员role: 1

        $rs[0]['role'] = "1";
        $user_online_last_time = (int) $this->redis->hget('user_online_last_time',$rs[0]['id']);
        $rs[0]['online'] = time() - 300 < $user_online_last_time ? 1 : 0;

        return $rs[0];
    }

};
