<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/7
 * Time: 17:22
 */


use Logic\Admin\BaseController;

/*
 * 客服统计 满意度占比
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计满意度占比';
    const DESCRIPTION = '客服统计满意度占比';
    
    const QUERY = [
        'page' => '页码',
        'page_size' => '每页大小',
        'start_time' => '查询开始时间',
        'end_time' => '查询结束时间',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'name' => 'int #评价等级名称',
            'count' => 'int #数量',
            'ratio' => 'string #问题数量百分比',
            'value' => 'string #问题数量百分比',
            'totalCount' => 'int #问题总数'
        ]
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {
        $param = $this->request->getParams();
        $site = $this->ci->get('settings')['ImSite'];
        $manage = new \Logic\Service\Manage($this->ci);
        if (!isset($param['start_time'])) {
            $param['start_time'] = date('Y-m-d') . ' 00:00:00';
        }else{
            $param['start_time'].= " 00:00:00";
        }
        if (!isset($param['end_time'])) {
            $param['end_time'] = date('Y-m-d') . ' 23:59:59';
        }else{
            $param['end_time'].= " 23:59:59";
        }
        $url = $site['url'] . '/customer_service/comment_count';
        $data = $manage->getBaseStatistics($url, $param);
        $result = [];
        if ($data['error'] == 0) {
            $res = array_values($data['data']['list']);
            $attributes['total'] = count($res);
            foreach ($res as $key => $val) {
                $res[$key]['value'] = $val['ratio'];
            }
            $result['list'] = $res;

            $result['title'] = array_map(function ($re) {
                return $re['name'];
            }, $res);
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];
            if (!$attributes['total'])
                return [];
            return $this->lang->set(0, [], $result, $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};