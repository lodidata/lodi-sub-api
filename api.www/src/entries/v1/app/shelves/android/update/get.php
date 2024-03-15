<?php

use Utils\Www\Action;

return new class extends Action {
    const HIDDEN = true;
    const TITLE = '上架包信息';
    const DESCRIPTION = '上架包信息';
    const QUERY = [
        'channel_id' => 'string(required) #渠道ID'
    ];
    const SCHEMAS = [
        'ip'            => 'int(required) #ID',
        'channel'       => 'string(required) #渠道',
        'channel_id'    => 'string(required) #渠道ID',
        'name'          => 'string(required) #APP名称',
        'status'        => 'enum[0,1](required,1) #审核不通过0，通过1',
        'force_update'  => 'int() #是否强制更新,1表示启用，2表示停用',
        'name_update'   => 'string() #更新包名称',
        'url_update'    => 'string() #更新包链接',
    ];

    public function run() {
        $channel_id = $this->request->getParam('channel_id');
        $device = $this->request->getHeaders()['HTTP_UUID'] ?? '';

        if (is_array($device)) {
            $device = array_shift($device);
        }

        $android = \Model\AppBag::where('channel_id', '=', $channel_id)->where('type','=',3)->first(['id','channel','channel_id','name','status','force_update','name_update','url_update']);
        if(is_null($android)){
            return $this->lang->set(886,[$this->lang->text('channel package does not exist')]);
        }else{
            $data = $android->toArray();
            return $this->lang->set(0, [], $data);
        }
    }
};