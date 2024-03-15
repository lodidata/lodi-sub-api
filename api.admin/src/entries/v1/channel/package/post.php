<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '添加渠道代理包';
    const DESCRIPTION = '添加渠道代理包';
    const PARAMS = [];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $params = $this->request->getParams();
        if((empty($params['channel_id']) && !is_array($params['channel_id'])) || empty($params['url']) || empty($params['name'])) {
            return $this->lang->set(10010);
        }
        if(!preg_match("/^(http:\/\/|https:\/\/).*$/", $params['url'])){
            return $this->lang->set(10010);
        }
        $return=array(
          'batch_no'=>time()
        );
        //根据type生成或添加
        foreach($params['channel_id'] as $value) {
            $data = [
                'name'       => $params['name'],
                'url'        => $params['url'],
                'channel_id' => $value,
                'status'     => 0,
            ];
            if($params['type'] == 2){
                $data['batch_no'] = $return['batch_no'];
                $data['status'] = 2;
            }
            DB::table('channel_package')->insert($data);
        }
        if($params['type'] == 2){
            \Utils\MQServer::send('channel_package', $return);
        }
        return $this->lang->set(0,[],$return);
    }

};
