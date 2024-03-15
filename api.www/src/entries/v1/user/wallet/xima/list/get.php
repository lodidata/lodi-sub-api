<?php
use Utils\Www\Action;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "洗码记录列表";
    const TAGS = "打码量";
    const DESCRIPTION = "\r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const SCHEMAS = [
    ];


    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();

        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 10);

        $query = DB::table('xima_order')
            ->where('user_id',$userId)
            ->selectRaw('id,order_number,user_name,dml_total,amount_total,created');

        $query = !empty($stime) ? $query->where('created','>=',$stime) : $query ;
        $query = !empty($etime) ? $query->where('created','<=',$etime.' 59:59:59') : $query ;

        $data = $query->forPage($page,$size)->orderByDesc('id')->get()->toArray();
        $count = clone $query;
        $attributes['total'] = $count->count();
        if($attributes['total']){
            foreach ($data as &$v){
                $v->info = DB::table('xima_order_detail as xod')
                            ->leftJoin('game_menu as gm','xod.game_type_id','=','gm.id')
                            ->selectRaw('name,xod.dml,tmp_percent,amount')
                            ->where('order_id',$v->id)->get()->toArray();
            }
        }

        $attributes['size'] = $size;
        $attributes['number'] = $page;


        return $this->lang->set(0, [], $data, $attributes);


    }
};