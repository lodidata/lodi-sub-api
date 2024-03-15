<?php
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{

    const TITLE = 'GET 查询 我的团队';

    const DESCRIPTION = '个人中心-我的团队';

    

    

    const QUERY = [
        'id' => 'int() #用户id',

    ];
    const PARAMS = [];
    const SCHEMAS = [
        [
            "id" => "1",
            "name" => "string #名称",
            "truename"=>"string #真实姓名",
            "mobile" => "int #绑定手机号",
            "earn_money" => "int #总返佣收益",
            "created" => "datetime #eg:2017-08-31 02:56:26 注册时间",
            "inferisors_num"=> "int #下级人数",
            "channel" => "dtring #eg:注册来源 @t:registe=网站注册, partner=第三方,reserved =保留 ...=各种客户端注册占用",
            "bkge_lottery"=> "int #彩票返佣比例",
            "bkge_sport"=> "int #体育返佣比例",
            "bkge_live"=> "int #视讯返佣比例",
            "bkge_game"=> "int #电子返佣比例",
            "sum_lottery_rake"=> "int #彩票总返佣收益",
            "sum_game_rake"=> "int #电子总返佣收益",
            "sum_live_rake"=> "int #视讯总返佣收益",
            "sum_sport_rake"=> "int #体育总返佣收益",
            "deposit"=> "int #充值总额",
            "sum_bet"=> "int #消费总额",
            "state"=> "int #账户状态(0禁用1启用2黑名单3删除4封号)",
        ],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id = '')
    {

        $params = $this->request->getParams();
        $params['uid_agent'] = $id;
        $params['page'] = $params['page'] ?? 1;
        $params['size'] = $params['page_size'] ?? 15;

        $subQuery = DB::table('user_agent')->selectRaw('user_id,inferisors_all,profit_loss_value')->where('uid_agent',$id);
        $total = $subQuery->count();
        $subTable = $subQuery->forPage($params['page'],$params['size'])->toSql();

        $res = DB::table(DB::raw("({$subTable}) as u"))
            ->mergeBindings($subQuery)
            ->leftJoin('user as u','u.user_id','=','u.id')
            ->leftJoin('user_level as l', 'u.ranting', '=', 'l.level')
            ->selectRaw('u.id,u.name,u.inferisors_all,u.profit_loss_value,u.created,l.name as ranting')
            ->get()
            ->toArray();
        if(!empty($res) && count($res) > 0){
            foreach($res as &$value){
                $profitValue=json_decode($value->profit_loss_value,true);
                if(!empty($profitValue)){
                    $value->profit_loss_value=max($profitValue);
                }else{
                    $value->profit_loss_value = 0;
                }

            }
        }
        $attr['total'] = $total;
        $attr['num'] = $params['page'];
        $attr['size'] = $params['page_size'];
        return $this->lang->set(0,[],$res,$attr);

    }


};
