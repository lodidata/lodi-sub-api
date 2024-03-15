<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class () extends BaseController {
    const TITLE = '获取直推统计';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $username = $this->request->getParam('username');
        $order_type = $this->request->getParam('order_type');
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 10);
        $sort = $this->request->getParam('sort', 'price');

        $query = DB::table('user_data as ud')
            ->leftJoin('user as u', 'u.id', '=', 'ud.user_id');
        if (!empty($username)) {
            $query = $query->where('u.name', $username);
        }
        $count = clone $query;
        $query = $query->groupBy('ud.user_id')->selectRaw('u.name as username,ud.direct_award as price,ud.direct_deposit as recharge_count,ud.direct_register as register_count');

        if ($order_type == 1) {
            $query = $query->orderBy($sort, 'asc');
        } else {
            $query = $query->orderBy($sort, 'desc');
        }

        $attributes['total'] = $count->count();
        $attributes['size'] = $size;
        $attributes['number'] = $page;

        $data = $query->forPage($page, $size)
            ->get()
            ->toArray();

        return $this->lang->set(0, [], $data, $attributes);
    }
};