<?php
use Utils\Www\Action;
use Model\Active;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '手动领取活动';
    const TAGS = "优惠活动";
    const QUERY = [
        'id' => 'int(required) #活动ID'
    ];
    const SCHEMAS     = [];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $activeId = $this->request->getParam('id') ? $this->request->getParam('id') : $this->request->getQueryParam('id');
        $recharge = new \Logic\Recharge\Recharge($this->ci);
        $active_apply = DB::table('active_apply as ap')
            ->leftJoin('active as a','ap.active_id','=','a.id')
            ->where('ap.user_id',$userId)
            ->where('ap.status','pending')
            ->where('ap.active_id',$activeId)
            ->selectRaw('ap.*,a.type_id')
            ->orderBy('ap.created')
            ->first();
        if(!$active_apply){
            return $this->lang->set(147);
        }
        if(in_array($active_apply->type_id,[2,3])){

            $date = date('Y-m-d');
            if($active_apply->type_id == 2){
                $memo = $this->lang->text("new people's first charge");
                $funds_deposit = DB::table('funds_deposit')->whereRaw("user_id = $userId and `status` = 'paid' and active_apply != '' and money>0")
                    ->orderBy('updated')->first();
            }elseif($active_apply->type_id == 3){
                $memo = $this->lang->text('daily first charge');
                $funds_deposit = DB::table('funds_deposit')->whereRaw("user_id = $userId and `status` = 'paid' and active_apply != ''and created >= '$date'  and money>0")
                    ->orderBy('updated')->first();
            }else{
                return $this->lang->set(147);
            }
            if($funds_deposit){

                $active_apply = DB::table('active_apply')->where('trade_id',$funds_deposit->id)->where('active_id',$activeId)->first();
                $active_apply_id = $active_apply->id;
                if(in_array($active_apply_id,explode(',',$funds_deposit->active_apply))){
                    return $recharge->updateActivity($userId, $active_apply_id,$funds_deposit->id,$memo,$funds_deposit->trade_no);
                }

                return $this->lang->set(147);
            }

        }else{
            return $recharge->addActivity($userId, $activeId, $memo = $this->lang->text('manual collection of event offers'));

        }
    }
};