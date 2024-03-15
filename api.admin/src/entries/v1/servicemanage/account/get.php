<?php
use Logic\Admin\BaseController;

return new class extends BaseController {

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {

        $params = $this->request->getParams();

        $query = DB::table('service_account');

        $query = isset($params['type']) && !empty($params['type']) ? $query->where('type',$params['type']) : $query ;

        $total = $query->count();
        $data = $query->forPage($params['page'],$params['page_size'])->get()->toArray();

        $attributes['total'] = $total;
        $attributes['number'] = $params['page'];
        $attributes['size'] = $params['page_size'];


        return $this->lang->set(0, [], $data, $attributes);
    }
};