<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/8
 * Time: 14:05
 */


use Logic\Admin\BaseController;

/*
 * 客服统计 客服绩效
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计客服绩效';
    const DESCRIPTION = '客服统计客服绩效';
    
    const QUERY = [
        'operator_name' => 'string #客服昵称',
        'find' => 'string #客服账号',
        'start_time' => 'string #查询开始时间， 如：2018-05-15 00:00:00',
        'end_time' => 'string #查询结束时间， 如：2018-05-15 23:59:59',
        'page_size' => 'int #每页条数，默认10。最大100，超过100则重置为100',
        'page' => 'int #页码，默认1'
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'uin' => 'int #客服id',
            'find' => 'string #客服账号',
            'nick_name' => 'string #客服昵称/名称',
            'online_status' => 'int #在线状态 =0离线；=1在线',
            'operator_status' => 'int #客服的状态，=0表示禁用，=1表示启用',
            'reg_time' => 'int #注册时间，时间戳，秒',
            'talks_num' => 'int #接待会话数',
            'talks_total_times_str' => 'string #会话总时长，已格式化，如：x分x秒',
            'talks_average_times_str' => 'string #平均会话时长，已格式化，如：x分x秒',
            'talks_total_times' => 'int #会话总时长，未格式化，秒数',
            'talks_average_times' => 'int #平均会话时长，未格式化，秒数',
            'comment_no' => 'int #未评价',
            'comment_good' => 'int #满意',
            'comment_ordinary' => 'int #一般',
            'comment_no_good' => 'int #不满意',
            'login_times_str' => 'string #登陆时长，已格式化，如：x分x秒',
            'login_times' => 'int #登陆时长，未格式化，秒数'
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
        $url = $site['url'] . '/service_result?' . http_build_query($param);
        $data = $manage->getBaseStatistics($url, [], 'GET');

        if ($data['error'] == 0) {
            $attributes['total'] = $data['data']['total'];
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];
            if (!$attributes['total'])
                return [];
            return $this->lang->set(0, [], $data['data']['data'], $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};