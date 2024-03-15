<?php

use Utils\Www\Action;
use Model\Advert;

return new class extends Action {
    const TITLE = "首页-轮播图";
    const TAGS = '首页';
    const QUERY = [
        "type" => "int(,1) #广告类型(1：pc，2：h5) 默认为PC",
    ];
    const SCHEMAS = [
        [
            "pic"      => "string() #图片地址",
            "language" => "string() #语言",
            "link"     => "string() #活动跳转链接",
            "link_type"=> "enum[1,2]() #是否关联活动 1不关联 2关联",
            "active_id"=> "string() #当关联活动时为活动链接 默认为空",
            "language_id"=> "int() 当前语言ID",
            "language_name" => "string() 当前语言名称"
        ],
    ];

    public function run()
    {
        $type = !$this->auth->isMobilePlatform() ? 1 : 2;
        $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['banner'] . $type);
        if (empty($data) || true) {
            $data = Advert::where('advert.type', 'banner')
                          ->leftjoin('active', 'advert.link', '=', 'active.id')
                          ->where('advert.pf', $type == 1 ? 'pc' : 'h5')
                          ->where('advert.status', 'enabled')
                          //->where('advert.language_id', $this->language_id)
                          ->whereRaw('(active.status = \'enabled\' OR advert.link_type != 2)')
                          ->selectRaw('advert.link_type,advert.link,advert.picture,type_id,advert.language_id')
                          ->orderby('advert.sort')
                          ->get()
                          ->toArray();
            $arr = [];
            if (!empty($data)) {
                foreach ($data as $v) {
                    $temp = [];
                    //$temp['m_pic'] = $v['picture'];
                    $temp['pic'] = $v['picture'];
                    $temp['link'] = $v['link'];
                    $temp['link_type'] = $v['link_type'];
                    //$temp['type_id'] = $v['type_id'];
                    $temp['active_id'] = '';
                    if ($temp['link_type'] == 2) {
                        $temp['active_id'] = $v['link'];
                    }
                   // $temp['language_id'] = $v['language_id'];
                    //$temp['language_name'] = $this->language_name;
                    array_push($arr, $temp);
                }

                $this->redis->setex(\Logic\Define\CacheKey::$perfix['banner'] . $type, 3600, json_encode($arr));
            }
        } else {
            $arr = json_decode($data, true);
        }

        foreach ($arr as &$v) {
            $v['pic'] = showImageUrl($v['pic']);
        }
        unset($v);
        return $arr;
    }
};