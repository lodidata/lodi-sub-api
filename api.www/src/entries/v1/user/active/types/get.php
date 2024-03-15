<?php
use Utils\Www\Action;
use Model\Active;

return new class extends Action {
    //const TOKEN = true;
    const TITLE = "获取优惠活动类型列表";
    const DESCRIPTION = "获取优惠活动类型列表信息";
    const TAGS = "优惠活动";
    const SCHEMAS = [
           [
               "id" => "int(required) #类型ID",
               "name" => "string(required) #类型名称",
               "description" => "string() #描述",
               "created" => "timestamp() #创建时间",
               "created_uname" => "string() #创建人",
               "updated" => "timestamp() #更新时间",
               "updated_uname" => "string() #更新人"
           ]
   ];

    public function run() {

        $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['activeTypes']);
        $data = empty($data) ? [] : json_decode($data, true);

        if (empty($data)) {
            $data = \Model\ActiveType::where('status', '=', 'enabled')->orderby('sort', 'asc')->select(['id', 'name', 'description', 'created'])->get()->toArray();
            $data = DB::resultToArray($data);
            $data = array_merge([["id" => "0", "name" => $this->lang->text("All activities"), "description" => "", "created" => ""]], $data);
            if($data){
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['activeTypes'], 30, json_encode($data, true));
            }

        }
        return $data;
    }
};