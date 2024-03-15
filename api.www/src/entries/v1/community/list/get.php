<?php
use Utils\Www\Action;
return new class extends Action
{
    const TITLE = "社区论坛列表";
    const TAGS = "社区论坛列表";
    const QUERY = [];
    const SCHEMAS = [
           [
               "name" => "string() #社区名称",
               "icon" => "string() #社区图标",
               "jump_url" => "string() #跳转链接",
           ]
   ];


    public function run() {
        //获取总开关
        $switch = \Model\Admin\SystemConfig::where('state','enabled')->where('key','community_bbs')->first();
        $data = [];
        if(empty($switch) || $switch->value == 1){
            $list = \DB::table('community_bbs')->select('name','icon','jump_url')->where('status','=',0)->orderBy('id','desc')->get();
            if(!$list->isEmpty()){
                $data = $list->toArray();
                foreach($data as &$val){
                    $val->icon = showImageUrl($val->icon);
                }
                unset($list, $val);
            }
        }

        return $this->lang->set(0, [], $data);
    }
};