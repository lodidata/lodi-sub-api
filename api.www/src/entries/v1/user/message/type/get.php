<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取消息列表-分消息-公告";
    const DESCRIPTION = "默认message_type为0:消息，1为公告\r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "会员消息";
    const QUERY = [
        "type"      => "enum[1,2](,1) #消息类型(1:重要消息,2:一般消息)",
        'page'      => "int(,1) #第几页 默认为第1页",
        "page_size" => "int(,10) #分页显示记录数 默认10条记录",
        'message_type' => "enum[0,1](,1) #消息分类(0:消息,1:公告)",
   ];
    const SCHEMAS = [
       [
           "id" => "int() #消息id",
           "status" => "enum[1,0](,0) #状态(1:已读,0:未读)",
           "title" => "string() #消息标题",
           "content" => "string() #消息内容",
           "time" => "string() #时间"
       ]
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        if($this->auth->getTrialStatus()) {
            return $this->lang->set(0, [], [], ['number' => 1, 'size' => 10, 'total' => 0]);
        }
        $type     = (int) $this->request->getQueryParam('type', 0);
        $page     = (int) $this->request->getQueryParam('page', 1);
        $pageSize = (int) $this->request->getQueryParam('page_size', 10);
        $id       = (int) $this->request->getQueryParam('id', 0);
        $message_type     = (int) $this->request->getQueryParam('message_type', 0);
        $ranting = \Model\User::where('id',$this->auth->getUserId())->value('ranting');
        // 更新消息状态
        if ($id > 0) {
            \Model\MessagePub::where('id', $id)
                ->update(['status' => 1]);
        }

        $data = DB::table('message_pub')
                    ->leftjoin('message', 'message.id', '=', 'message_pub.m_id')
                    ->where('message_pub.status', '!=', -1);

        if ($id > 0) {
            $data = $data->where('message.id', $id);
        }

        if ($type > 0) {
            $data = $data->where('message.type', $type);
        }
        //后台发消息为公告，管理员ID大于0
        if($message_type){
            $data = $data->where('message.admin_uid', '>', 0);
        }

        $data = $data->select([
                    'message_pub.id', 
                    'message_pub.status', 
                    DB::raw('from_unixtime(message_pub.created) as start_time'), 
                    'message.title', 
                    'message.content', 
                    'message.send_type', 
                    'message.type',
                    DB::raw('FROM_UNIXTIME(message_pub.created) as start_time'),
                ])
                ->where('message_pub.uid', $this->auth->getUserId())
                ->orderBy('message_pub.id', 'DESC')
                ->paginate($pageSize, ['*'], 'page', $page);
        $result = $data->toArray()['data'];
        if($result){
            foreach ($result as &$v){
                $v->title   = $this->translateMsg($v->title);
                $v->content = $this->translateMsg($v->content);
            }
            unset($v);
        }
        return $this->lang->set(0, [], $result, ['number' => $page, 'size' => $pageSize, 'total' => $data->total()]);
    }
};
