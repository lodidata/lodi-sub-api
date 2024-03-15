<?php
use Logic\Admin\BaseController;
use Model\Admin\Active as ActiveModel;

return new class() extends BaseController
{
    const TITLE       = '批量赠送彩金活动';
    const DESCRIPTION = '批量赠送彩金活动';
    const QUERY       = [
        'page'=>'integer() #',
        'page_size' => 'integer() #',
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run()
    {
        $params = $this->request->getParams();
        $page = $params['page'] ?? 1;
        $page_size = $params['page_size'] ?? 20;
        $stime     = $params['start_time'] ?? 0;
        $etime     = $params['end_time'] ?? 0;
        $status    = $params['status'] ?? 0;

        $query =  DB::connection('slave')->table('active as a')->leftJoin('active_handsel as h','a.id', '=','h.active_id')
            ->leftJoin('active_template as at','a.type_id', '=','at.id')
            ->leftJoin('active_type as aty','a.active_type_id', '=','aty.id')
            ->leftJoin('active_handsel_log as ahl','ahl.active_handsel_id', '=','h.id')
            ->select([
                'a.id as active_id','a.title','a.name','a.title','a.active_type_id','a.type_id as template_id','a.begin_time','a.end_time','a.state','a.status', 'a.sort','a.created',
                'h.id as handsel_id','h.msg_title','h.msg_content','h.give_away','h.phone_list','h.user_level','h.batch_url','h.give_amount','h.dm_num','h.notice_type','h.give_amount_time','h.uid_list',
                'h.is_now_give','h.limit_game','h.recharge_num','h.valid_time','h.unfixed_url','h.receive_way','h.recharge_limit','h.recharge_type','h.recharge_coin','ahl.give_num','ahl.id as log_id','h.state as is_enabled'
            ])->whereRaw('a.type_id=? and a.status != ?', [12,"deleted"]);
        if($stime) {
            $query->where('a.created', '>=', $stime);
        }
        if($etime) {
            $query->where('a.created', '<=', $etime.' 23:59:59');
        }
        if($status) {
            switch ($status){
                case 'sending':
                    $query->where('h.state', '=', 0);
                    break;
                case 'disabled':
                    $query->where('a.status', '=', $status);
                    break;
                case 'success':
                    $query->whereRaw('a.id in'.\DB::raw("(select active_id from active_apply group by active_id)"));
                    break;
                case 'fail':
                    $query->where('h.state', '=', 1);
                    $query->whereRaw('a.id not In'.\DB::raw("(select active_id from active_apply group by active_id)"));
                    break;
                default:
                    echo 1;
                    break;
            }
        }

        $attributes['total'] = $query->count();
        $attributes['number'] = $page;
        $res = $query->orderBy('sort')->orderBy('a.id','DESC')->forPage($page,$page_size)->get()->toArray();
        if(!$res){
            return [];
        }
        //发送状态拼装
        foreach ($res as &$itme ){
            $itme->send_status = $this->lang->text('Sending');
            $str                 = $itme->status == 'enabled' ? '当前已发送' : '发送终止';
            $count               = DB::table('handsel_receive_log')->where('handsel_log_id',$itme->log_id)->count();
            if($itme->is_enabled == 1 && $count == 0 ) $itme->send_status = '发送失败';
            if ($count > 0 && $itme->give_num > 0) {
                $itme->send_status = $str.ceil($count / $itme->give_num * 100) .'%';
                if (ceil($count/$itme->give_num * 100) == 100){
                    $itme->send_status = $this->lang->text('Sent completed');
                }
            }
            $itme->batch_url = showImageUrl($itme->batch_url);
            $itme->unfixed_url = showImageUrl($itme->unfixed_url);
        }
        $game_info = DB::table('game_menu')->whereRaw('status=? and pid=?',['enabled',0])->get()->toArray();
        $game_data = [];
        foreach ($game_info as $v) {
            $game_data[] = [
                'id' => $v->id,
                'type' => $v->type,
                'game_name' => $v->rename
            ];
        }
        $attributes['game_data'] = $game_data;
        return $this->lang->set(0,[],$res,$attributes);
    }
};