<?php
use Utils\Www\Action;
use Model\Active;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取手动领取活动列表";
    const TAGS = "优惠活动";
    const QUERY = [
        "type_id"   => "int(required) #类型ID"
    ];
    const SCHEMAS = [
       [
           'id'                => 'int(required) #ID',
           "status"            => "string() #状态值(pass:通过, rejected:拒绝, pending:待处理, undetermined:未解决的)",
           "state"             => "string() #设置状态,有apply就显示,没有就不显示(apply:可申请, auto:自动参与,manual:手动的)",
       ]
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $typeId = $this->request->getQueryParam('type_id');
        if(!is_numeric($typeId)){
            return $this->lang->set(-2);
        }

        $active = DB::table('active_apply')
            ->leftjoin('active', 'active_apply.active_id', '=', 'active.id')
            ->selectRaw('active.id,active_apply.status,active_apply.state')
            ->where('active.type_id', $typeId)
            ->where('active_apply.status', 'pending')
            ->where('active_apply.state', 'manual')
            ->where('active_apply.user_id', $this->auth->getUserId())
            ->orderBy('active_apply.created','desc')
            ->get()->toArray();

        if(!$active) {
            return '';
        }
        return $active;
    }
};