<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '代理包统计列表';
    const DESCRIPTION = '获取代理包统计信息';
    
    const QUERY = [
        'channel_id' => 'string(, 30)',
        'page'       => 'int()  #页码',
        'page_size'  => 'int()  #每页大小',
    ];

    const PARAMS = [];
    const SCHEMAS = [
            [
                'app'        => 'string APP名称',
                'channel'    => 'string 渠道',
                'channel_id' => 'string #渠道ID',
                'count'      => '激活数量',
            ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        DB::enableQueryLog();

        $channel = $this->request->getParam('channel_id', false);
        $name = $this->request->getParam('name', false);
        $app = $this->request->getParam('app', false);
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 20);

        $query = \DB::table('app_active_log AS log');

        if ($channel) {
            $query->where('channel_id', '=', $channel);
        }

        if ($name) {
            $query->where('app', '=', $name);
        }

        if ($app) {
            $query->where('app`', 'like', '%' . $app . '%');
        }

        $query->groupBy('channel_id')
              ->forPage($page, $page_size);

        $total = \DB::selectOne('SELECT COUNT(*) AS `count` FROM `app_active_log` GROUP BY `channel_id`');
        if ($total) {
            $total = $total->count;
        } else {
            $total = 0;
        }

        $data = $query->get([
            'channel_id',
            'channel',
            'app',
            \DB::raw('COUNT(1) AS `count`'),
            \DB::raw('COUNT(DISTINCT ip) AS `ip_count`'),
        ])
                      ->toArray();

        $attr = [
            'number' => $page,
            'size'   => $page_size,
            'total'  => $total->count ?? 0,
        ];

        return $this->lang->set(0, [], $data, $attr);
    }
};
