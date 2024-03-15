<?php
/**
 * 会员层级列表
 * @author Taylor 2019-01-10
 */
use Logic\Admin\BaseController;
use Logic\Level\Level as level;

return new class() extends BaseController {
    const TITLE = '等级列表';
    const QUERY = [
        'page'=>'int() #当前页',
        'page_size'=>'int() #每页显示条数',
    ];
    const SCHEMAS = [
        'name' => 'string #等级名称',
        'deposit_money' => 'int #最低充值金额',
        'online_dml_percent' => 'float #线上充值打码量',
        'offline_dml_percent' => 'float #线下充值打码量',
        'level' => 'int #数字等级',
        'icon' => 'string #等级图标URL地址',
        'lottery_money' => 'int #最低投注量',
        'user_count' => 'int #该层级对应的会员人数',
        'upgrade_dml_percent' => 'float #提现打码量',
        'draw_count' => 'int #活动免费抽奖次数',
        'promote_handsel' => 'int #晋升彩金',
        'transfer_handsel' => 'int #转卡彩金',
        'monthly_money' => 'int #月俸禄彩金，分为单位',
        'monthly_percent' => 'int #月俸禄条件百分比',
        'online'=> 'array #层级对应的线上支付列表',
        'offline'=> 'array #层级对应的线下支付列表',
        'bankcard_sum' => 'int #银行卡绑定数',
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {
        $params = $this->request->getParams();
        $levelLogic = new Level($this->ci);

        $query = DB::table('user_level')
            ->select(['id', 'name', 'deposit_money', 'online_dml_percent', 'offline_dml_percent',
        'level','icon','lottery_money','user_count','upgrade_dml_percent','draw_count','promote_handsel','transfer_handsel','monthly_money','monthly_percent','monthly_recharge', 'bankcard_sum','background','fee','week_recharge','level_background','welfare','week_money', 'split_line', 'font_color']);

        if (isset($params['id']) && !empty($params['id'])) {
            $query->where('id', $params['id']);

        }

        $attr['total'] = $query->count();
        $levelData = $query->forPage($params['page'], $params['page_size'])->get()->toArray();
        $attr['num'] = $params['page'];
        $attr['size'] = $params['page_size'];

        //获取支付设置
        foreach ($levelData as $k => &$v) {
            $v->online = $levelLogic->getLevelOnlineSet($v->id);
            $v->offline = $this->getLevelOfflieSet($v->id);
            if ($v->welfare) $v->welfare = json_decode($v->welfare,true);
            $v->icon = showImageUrl($v->icon);
            $v->level_background = showImageUrl($v->level_background);
            $v->background = showImageUrl($v->background);
            $v->split_line = showImageUrl($v->split_line);
            $v->week_award_day  = $this->redis->get(\Logic\Define\CacheKey::$perfix['week_award_day']);//设置回水时间
            $week_award_time = $this->redis->get(\Logic\Define\CacheKey::$perfix['week_award_time']);
            if(empty($week_award_time)){
                $week_award_time = '00:00:00';
            }
            $v->week_award_time = $week_award_time;//设置回水时间
        }
        unset($v);
        //设置周薪发放时间

        return $this->lang->set(0, '', $levelData, $attr);
    }

    /*
    * 获取层级线下支付列表 需连表查出名称，数据库只存了ID
    */
    protected function getLevelOfflieSet($levelId) {
        $data = DB::table('level_offline as l')
            ->where('l.level_id', $levelId)
            ->leftJoin('bank_account as b', 'l.pay_id', '=', 'b.id')
            ->whereRaw("find_in_set('enabled',state)")
            ->select(['b.name', 'b.type'])
            ->get()
            ->toArray();
        $result = [];
        $typeArr = ['1' => '网银', '2' => '支付宝', '3' => '微信', '4' => 'QQ支付', '5' => '京东支付'];
        foreach ($data ?? [] as $k => $v) {
            $result[] = $typeArr[$v->type] . "-" . $v->name;
        }
        return $result;
    }

};
