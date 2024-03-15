<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '13/14特殊赔率';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'lottery_id' => 'int()#彩种ID'
    ];

    const PARAMS      = [];
    const SCHEMAS     = [

    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {

        $params = $this->request->getParams();
        $query = DB::table('pc28special as p')
            ->leftJoin('hall as h','p.hall_id','=','h.id')
            ->selectRaw('p.*,h.hall_name,h.hall_level')
//            ->whereNotIn('p.hall_level',[4,5]);
            ->whereNotIn('p.hall_level',[4]);

        if(isset($params['lottery_id']) && $params['lottery_id']){
            $query = $query->where('p.lottery_id',$params['lottery_id']);
        }

        $data = $query->get()->toArray();

        if($data){
            return $data;
        }else{
            return [];
        }


    }

};
