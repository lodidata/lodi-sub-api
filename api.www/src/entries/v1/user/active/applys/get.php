<?php


/**
 * 获得优惠的资金明细表
 */

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获得优惠的资金明细表";
    const DESCRIPTION = "获得优惠的资金明细表   \r\n返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "优惠活动";
    const QUERY = [
        "type_id"       => "int() #类型ID",
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,8) #分页显示记录数 默认8条记录"
    ];
    const SCHEMAS = [
        [
            'id'                => 'int(required) #ID',
            "type_id"           => "int(required) #模板ID",
            "user_name"         => "string(required) #用户名",
            "active_id"         => "int(required) #活动ID",
            "active_name"       => "string(required) #活动名称",
            "deposit_money"     => "float() #存款(非存款活动没有存款这一项) 如：0",
            "coupon_money"      => "float() #优惠金额(手动申请的活动没有优惠金额) 如：1000",
            "withdraw_require"  => "float() #取款条件(手动申请的活动没有取款条件) 如：10",
            "apply_time"        => "dateTime() #申请时间 如：2019-01-09 10:51:51",
            "updated"           => "dateTime() #更新时间 如：2019-01-09 10:51:51",
            "content"           => "string() #申请内容",
            "memo"              => "string() #备注",
            "template"          => "string() #模板名称",
            "status"            => "enum[pass,rejected,pending,undetermined](,pass) #状态值(pass:通过, rejected:拒绝, pending:待处理, undetermined:未解决的)",
            "state"             => "enum[apply,auto,manual](,apply) #设置状态,有apply就显示,没有就不显示(apply:可申请, auto:自动参与,manual:手动的)",
            "process_time"      => "dateTime() #处理时间 如：2019-01-09 10:51:51",
        ]
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $params = $this->request->getParams();
        $page = isset($params['page']) ? $params['page'] : 1;
        $pagr_size = isset($params['page_size']) ? $params['page_size'] : 8;
        return $this->getActieApplys($params,$page, $pagr_size);

    }

    protected function getActieApplys($params,$page, $size)
    {
        $start_time = $params['start_time'] ?? '';
        $end_time = $params['end_time'] ?? '';
        $userId = (int)$this->auth->getUserId();
        $query = DB::table('active_apply as ap')
            ->leftJoin('active as a', 'ap.active_id', '=', 'a.id')
            ->leftJoin('active_template as at', 'a.type_id', '=', 'at.id')
            ->leftJoin('user as u', 'u.id', '=', 'ap.user_id')
            ->select(DB::raw(
                'ap.id,a.type_id,ap.user_name,ap.active_id,a.title as active_name,ap.deposit_money,ap.coupon_money,ap.withdraw_require,ap.apply_time,ap.updated,ap.content,ap.memo,at.name as template,ap.status,ap.state,ap.updated as process_time'
            ))
            ->whereNotIn('u.tags', [4, 7])
            ->where('ap.status', '<>', 'undetermined')
            ->where('ap.user_id', '=', $userId);

        if (isset($params['type_id']) && !empty($params['type_id'])) {
            $query = $query->where('a.type_id', $params['type_id']);
        }
        if(isset($params['start_time']) && !empty($params['start_time'])){
            $query = $query->where('ap.apply_time', '>=', $params['start_time'].' 00:00:00');
        }
        if(isset($params['end_time']) && !empty($params['end_time'])){
            $query = $query->where('ap.apply_time', '<=', $params['end_time'].' 23:59:59');
        }

        $attributes['total'] = $query->count();
        $res = $query->orderBy('ap.created', 'desc')
            ->forPage($page, $size)
            ->get()
            ->toArray();


        if (!$res) {
            return [];
        }

        $attributes['number'] = $page;

        return $this->lang->set(0, [], $res, $attributes);
    }
};
