<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/8/8
 * Time: 11:39
 */

use Logic\Admin\BaseController;

/*
 * 客服统计 聊天记录
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 客服统计聊天记录';
    const DESCRIPTION = '客服统计聊天记录';
    
    const QUERY = [
        'room_id' => 'int #会话房间id',
        'page_size' => 'int #每页条数，默认10。最大100，超过100则重置为100',
        'page' => 'int #页码，默认1'
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'msg_id' => 'int #聊天记录id',
            'send_time' => 'string #消息发送时间',
            'operator_uin' => 'string #客服id',
            'room_id' => 'int #会话房间id',
            'content' => 'string #聊天内容',
            'operator_name' => 'string #接待客服昵称',
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
        $url = $site['url'] . '/message?' . http_build_query($param);
        $data = $manage->getBaseStatistics($url, [], 'GET');
        if ($data['error'] == 0) {
            $attributes['total'] = $data['data']['total'];
            $attributes['number'] = $param['page'];
            $attributes['size'] = $param['page_size'];
            if (!$attributes['total'])
                return [];
            $result = $data['data']['data'];

            $hashids = $manage->getHashids();

            foreach ($result as $key => $value) {
                if (isset($value['client_third_party_id'])) {
                    $value['client_third_party_id'] = base_convert($value['client_third_party_id'], 10, 36);
                    $id = $hashids->decode($value['client_third_party_id']);
                    $username = '';
                    if (!empty($id)) {
                        $id = $id[0];
                        $user_type = $manage->getUserType($id);
                        if ($user_type == 2) {//试玩用户类型
                            $id = $id - 10000000000;
                            $user = \Model\TrialUser::find($id);;
                            $username = '试玩' . $user->name;
                        } else if ($user_type == 3) {//游客用户类型
                            $username = '游客' . $id;
                        } else {
                            $user = \Model\User::find($id);
                            if (!empty($user)) {
                                $username = $user->name;
                            }
                        }
                    }
                    if (!empty($username)) {
                        $result[$key]['username'] = $username;
                    } else {
                        $result[$key]['username'] = '异常用户' . $value['client_third_party_id'];
                    }
                } else {
                    $result[$key]['username'] = "";
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