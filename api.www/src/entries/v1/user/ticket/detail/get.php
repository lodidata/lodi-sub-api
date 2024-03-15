<?php

use Utils\Www\Action;

/**
 * 获取详情
 */
return new class extends Action
{
    const TITLE = "帮助中心-分类下详情列表";
    const TAGS = "帮助中心";
    const QUERY = [
        'id'    => 'int(required) #分类ID'
    ];
    const SCHEMAS = [
        [
            'ticket_id' => 'int(required) #分类ID',
            'title' => "string(required) #问题标题",
            "desc" => "string(required) #问题描述",
            "lang" => "string(required) #语言代码 zh-cn",
        ]
    ];

    public function run()
    {
//        $verify = $this->auth->verfiyToken();
//        if (!$verify->allowNext()) {
//            return $verify;
//        }

        $id = $this->request->getParam('id');

        if(!$id){
            return $this->lang->set(886,[$this->lang->text("ID cannot be empty")]);
        }
        $data = DB::table('ticket_content')
            ->select(['ticket_id','title','desc','lang'])
            ->where('ticket_id', '=', $id)
            ->get()
            ->toArray();
        return $data;
    }

};