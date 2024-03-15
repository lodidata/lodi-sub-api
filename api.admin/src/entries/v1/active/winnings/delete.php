<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '批量赠送彩金活动';
    const DESCRIPTION = '启用和禁用活动';
    const QUERY = [
        'title' => 'string() #活动名称',
        'template_id' => 'integer() #模板ID',
        'status' => 'string() #enabled（启用），disabled（停用）',
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run()
    {
        $params = $this->request->getParams();
        $active_id = $params['active_id'] ?? 0;
        $status = $params['status'] ?? '';

        if (empty($active_id) || empty($status)) {
            return createRsponse($this->response, 200, -2, '缺少必要参数');
        }

//        $res = DB::table("active")->where('id', $active_id)->delete();
        $res = DB::table("active")->where('id', $active_id)->update(['status'=>'deleted']);
        if (!$res) {
            return createRsponse($this->response, 200, -2, '删除活动失败');
        }

        //把 active_handsel 状态改为删除
        $result = DB::table("active_handsel")->where('active_id', $active_id)->where('status',1)->update(['status'=>0]);
        if($result){
            //删除redis
            $active_handsel_info = DB::table("active_handsel")->where('active_id', $active_id)->where('status',0)->first(['id','create_time']);
            if($active_handsel_info){
                $active_handsel_id   = $active_handsel_info->id;
                $active_handsel_time = $active_handsel_info->create_time;
                //因为没有判定状态 就全部删一遍
                //彩金固定模式
                $this->redis->srem("immediately_send_handsel", $active_handsel_id);   //删除立即赠送列表中的活动id
                $this->redis->del("immediately_send_handsel_item:".$active_handsel_id);   //定时赠送的活动用户队列
                $this->redis->srem("timer_send_handsel", $active_handsel_id);   //删除定时赠送列表中的活动id
                $this->redis->del("timer_send_handsel_item:".$active_handsel_id);   //定时赠送的活动用户队列

                //彩金非固定模式
                $this->redis->srem("immediately_unfixed_keys", $active_handsel_id);   //删除立即赠送列表中的活动id
                $this->redis->del("immediately_unfixed_list:".$active_handsel_id);   //立即赠送的活动用户队列
                $this->redis->srem("timer_unfixed_keys", $active_handsel_id);   //删除定时赠送列表中的活动id
                $this->redis->del("timer_unfixed_list:".$active_handsel_id);   //定时赠送的活动用户队列

                //删除消息
                DB::table("message")->where('active_id', $active_handsel_id)->whereRaw("created >= unix_timestamp('{$active_handsel_info->create_time}')")->delete();

            }
        }

        return $this->lang->set(0,[],$res,[]);
    }
};
