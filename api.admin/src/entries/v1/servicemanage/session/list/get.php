<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/8
 * Time: 10:44
 */


use Logic\Admin\BaseController;

/*
 * 客服统计 会话记录列表
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计会话记录列表';
    const DESCRIPTION = '客服统计会话记录列表';
    
    const QUERY = [
        'client_third_party_id' => 'int #会员id，第三方应用的用户id',
        'operator_name' => 'string #接待客服昵称/名称',
        'question_type' => 'string #问题类型',
        'comment_level' => 'int #满意度',
        'end_mode' => 'int #会话结束方式',
        'start_time' => 'dateTime #查询开始时间， 如：2018-05-15 00:00:00',
        'end_time' => 'dateTime #查询结束时间， 如：2018-05-15 23:59:59',
        'page_size' => 'int #每页条数，默认10。最大100，超过100则重置为100',
        'page' => 'int #页码，默认1'
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'room_id' => 'int #会话id',
            'room_start_time' => 'string #会话开始时间',
            'room_end_time' => 'string #会话结束时间',
            'client_third_party_id' => 'int #第三方会员ID',
            'operator_uin' => 'int #接待客服id',
            'operator_name' => 'string #接待客服昵称',
            'room_receive_time' => 'string #开始接待时间',
            'question_type' => 'string #问题类型',
            'end_mode' => 'string #string',
            'comment_level' => 'string #满意度',
            'comment_content' => 'string #客户评价',
            'wait_times_str' => 'string #等待时长，已经格式化为 x分x秒',
            'talks_times_str' => 'string #会话时长，已经格式化为 x分x秒',
            'wait_times' => 'int #等待时长，秒数，未格式化',
            'talks_times' => 'int #会话时长，秒数，未格式化'
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

        $hashids = $manage->getHashids();


        if (isset($param['client_third_party_id'])) {

            $client_third_party_id = $param['client_third_party_id'] . '%';
            $user = \Model\User::where('name', 'like', $client_third_party_id)->pluck('id')->toArray();
            if (empty($user)) {
                $param['client_third_party_id'] = 0;
            } else {
                foreach ($user as $key => $value) {
                    $value = $hashids->encode($value);
                    $value = base_convert($value, 36, 10);
                    $user[$key] = $value;
                }
                $param['client_third_party_id'] = implode(',', $user);
            }

        }

        //$url = $site['url'] . '/talks_log?'.http_build_query($param);
        $url = $site['url'] . '/talks_log';
        $data = $manage->getBaseStatistics($url, $param);

        if ($data['error'] == 0) {
            $attributes['total'] = $data['data']['total'];
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];
            if (!$attributes['total'])
                return [];
            $result = $data['data']['data'];

            foreach ($result as $key => $val) {
                $val['client_third_party_id'] = base_convert($val['client_third_party_id'], 10, 36);
                $id = $hashids->decode($val['client_third_party_id']);
                $username = '';
                if (!empty($id)) {
                    $id = $id[0];
                    $user_type = $manage->getUserType($id);
                    if ($user_type == 2) {//试玩用户类型
                        $id = $id - 10000000000;
                        $user = \Model\TrialUser::find($id);;
                        $username = '试玩' . $user->name;
                        $result[$key]['user_type'] = 2;
                    } else if ($user_type == 3) {//游客用户类型
                        $username = '游客' . $id;
                        $result[$key]['user_type'] = 3;
                    } else {
                        $user = \Model\User::find($id);
                        if (!empty($user)) {
                            $username = $user->name;
                            $result[$key]['user_type'] = 1;
                        }
                    }
                }
                $result[$key]['user_id'] = $id;
                if (!empty($username)) {
                    $result[$key]['client_third_party_id'] = $username;
                } else {
                    $result[$key]['client_third_party_id'] = '异常用户' . $val['client_third_party_id'];
                }
            }
            return $this->lang->set(0, [], $result, $attributes);
        } else if ($data['error'] == 1) {
            return $this->lang->set(10580);
        } else {
            return $this->lang->set(10581, [$data['msg']]);
        }


    }
};