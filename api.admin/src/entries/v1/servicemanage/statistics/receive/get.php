<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/7
 * Time: 17:26
 */


use Logic\Admin\BaseController;

/*
 * 客服统计 按时间节点接待数量统计
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计按时间节点接待数量统计';
    const DESCRIPTION = '客服统计按时间节点接待数量统计';

    const QUERY = [
        'page' => '页码',
        'page_size' => '每页大小',
        'start_time' => '查询开始时间',
        'end_time' => '查询结束时间',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'hour' => 'array #时间节点',
            'day' => 'array #日期节点',
            'value' => 'string #节点值',
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
        $url = $site['url'] . '/customer_service/receive_count';
        $result = [];
        $data = $manage->getBaseStatistics($url, $param);
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
        if ($data['error'] == 0) {
            if (abs(strtotime($param['end_time']) - strtotime($param['start_time'])) > 24 * 3600) {
                $result['day'] = array_keys($data['data']);
                $result['value'] = array_values($data['data']);
            } else {
                $result['hour'] = ['0:00','3:00','6:00','9:00','12:00','15:00','18:00','21:00','24:00'];
                $datas = [];
                foreach ($data['data'] as $key=>$val){
                    if (in_array($key,[0,3,6,9,12,15,18,21,24])){
                        $datas[] = $val;
                    }
                }
                $result['value'] = $datas;
            }
            $attributes['total'] = count($data['data']);
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