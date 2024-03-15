<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/25 20:26
 */

use Logic\Admin\BaseController;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = '注单查询--视讯类';
    const DESCRIPTION = '包括MG、LEBO、CT等';
    const HINT = '
        tableId #桌号
        局号 extra 字段：
            LEBO round_no
            DG  ext
            BG  serialNo
            AG  round
            BBIN    roundId
            AB  gameRoundId
            OG  roundId
    ';
    const QUERY = [
        //  'type_name'    => 'enum[LEBO,MG,CT,BBIN,AG,OG,BG,DG](required) #视讯名称',
        'user_name'    => 'string #用户名',
        'order_number' => 'string #注单号',
        'time_begin'   => 'datetime #投注时间，开始',
        'time_end'     => 'datetime #投注时间，结束',
        'page'         => 'int #当前页数',
        'page_size'    => 'int #一页多少条数',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            "user_name"    => "用户名",
            "type_name"    => "游戏类型",
            "bet_content"  => "投注内容",
            "account"      => "第三方帐号",
            "name"         => "",
            "game_name"    => "游戏名称乐",
            "date"         => "投注时间",
            "order_number" => "注单号",
            "money"        => "投注金额",
            "valid_money"  => "有效投注",
            "prize"        => "奖金",
            "win_loss"     => "输赢",
            "totalPrice"   => "总输赢。后台暂时不需要",
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();

        $query = DB::connection('slave')->table('order_3th as o')
                   ->leftJoin('user_agent as a', 'a.user_id', '=', 'o.user_id')
                   ->where('o.order_type', 2)
                   ->where('o.type_name', $params['type_name']);

        //  $query = isset($params['type_name']) && !empty($params['type_name']) ? $query->where('o.type_name',$params['type_mame']) : $query ;
        $query = isset($params['user_id']) && !empty($params['user_id']) ? $query->where('o.user_id', $params['user_id']) : $query;
        $query = isset($params['user_name']) && !empty($params['user_name']) ? $query->where('o.user_name', $params['user_name']) : $query;
        $query = isset($params['order_number']) && !empty($params['order_number']) ? $query->where('o.order_number', $params['order_number']) : $query;
        $query = isset($params['start_time']) && !empty($params['start_time']) ? $query->where('o.date', '>=', $params['start_time']) : $query;
        $query = isset($params['end_time']) && !empty($params['end_time']) ? $query->where('o.date', '<=', $params['end_time'] . '23:59:59') : $query;

        $query = $query->select([
            'o.user_name',
            'o.account',
            'o.type_name',
            'o.bet_content',
            'o.tableId',
            'a.uid_agent_name AS agent_name',
            'o.game_name',
            'o.date',
            'o.order_number',
            'o.3th_order_number',
            'o.money',
            'o.valid_money',
            'o.prize',
            'o.win_loss',
            'o.extra',
        ]);

        $attr['total'] = $query->count();

        $res = $query->orderBy('o.date', 'desc')
                     ->forPage($params['page'], $params['page_size'])
                     ->get()
                     ->toArray();

        //  dd(DB::getQueryLog());exit;

        if (!$res) {
            return [];
        }
        $attr['number'] = $params['page'];
        foreach ($res as &$datum) {
            $datum->extra = json_decode($datum->extra, true);
            //  $datum->bet_content  = json_decode($datum->bet_content, true);
        }

        return $this->lang->set(10200, [], $res, $attr);
    }
};
