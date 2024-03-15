<?php
use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "客服常见问题列表";
    const DESCRIPTION = "客服常见问题列表";
    const TAGS = '客服';
    const SCHEMAS = [
       [
           "id"         => "int(required) #ID",
           "question"   => "string(required) #客户提的问题",
           "answer"     => "string(required) #客服给的回答",
           "language_id"=> "int() #语言ID"
       ]
   ];

    public function run() {
        return \Model\ServiceList::get()->toArray();
    }
};