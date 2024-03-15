<?php
use Utils\Www\Action;
use Model\Notice;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "h5首页公告消息";
    const DESCRIPTION = "返回消息字段";
    const TAGS = "首页";
    const QUERY = [
        "type"       => "enum[2,3] #公告类型(2:视讯,3:体育)",
    ];
    const SCHEMAS = [
       [
           "id" => "int(required) #公告id",
           "title"  => "string(required) #公告标题",
           "content" => "string(required) #公告内容",
           "start_time" => "string(required) #开始显示时间  2012-01-01 12:12:12",
           "popup_type" => "int(required) #弹出类型(1：登录弹出，2：首页弹出，3：滚动公告）",
           "created"    => "string(required) #创建时间 2012-01-01 12:12:12"
       ]
   ];


    public function run() {
        $time = time();
        $verify = $this->auth->verfiyToken();
        $type = (int) $this->request->getQueryParam('type', 0);
        $login = [];
        $ranting = 0;
        if ($verify->allowNext()) {
            $ranting = \Model\User::where('id', $this->auth->getUserId())->value('ranting');
        }
        $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['noticeH5list'].'LV'.$this->auth->getUserId());
        if (empty($data)) {
            if ($verify->allowNext()) {
                $query = Notice::where('status', '=', 1)
                    ->where('popup_type', 1)
                    //->where('language_id', $this->language_id)
                    ->whereRaw("FIND_IN_SET('LV{$ranting}',recipient)")
                    ->where('title', '!=', "")
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>=', $time);
                $type && $query->where('type','=',$type);
                $login =   $query->orderBy('sort','asc') ->orderBy('updated','desc')->get(['id', 'title', 'content', 'imgs','popup_type', DB::raw('from_unixtime(start_time) start_time'),DB::raw('from_unixtime(created) created'),DB::raw('from_unixtime(updated) updated')])->toArray();
            }
            $query = Notice::where('status', '=', 1)
                ->where('popup_type','!=',1)
                //->where('language_id', $this->language_id)
                ->where('title', '!=', "")
                ->where('start_time', '<=', $time)
                ->where('end_time', '>=', $time);
            $type && $query->where('type','=',$type);
            $data =     $query->orderBy('sort','asc') ->orderBy('updated','desc')
                    ->get(['id','title','content','imgs','popup_type',DB::raw('from_unixtime(start_time) start_time'),DB::raw('from_unixtime(created) created'),DB::raw('from_unixtime(updated) updated')])->toArray();
             $data = array_merge($data,$login);

            $this->redis->setex(\Logic\Define\CacheKey::$perfix['noticeH5list'].'LV', 60, json_encode($data));
        } else {
            $data = json_decode($data, true);
        }

        foreach($data as $key => &$val){
            if(is_object($val)){
                $val->imgs = showImageUrl($val->imgs);
            }else{
                $val['imgs'] = showImageUrl($val['imgs']);
            }
        }
        unset($val);

        return $data;
    }
};