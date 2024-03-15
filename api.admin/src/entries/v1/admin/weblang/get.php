<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE            = '语言列表';
    const DESCRIPTION      = '';
    protected $langDirPath = "/../../../../../../config/lang";
    const QUERY     = [];
    const PARAMS    = [];
    const SCHEMAS   = [];
    const LANG      = [
        'lodi'  => 'en-us',
        'mxn'   => 'es-mx',
        'ncg'   => 'th',
    ];


    public function run() {

        $lang = [];
        //根据配置文件获取site
        $site_type        = $this->ci->get('settings')['website']['site_type'] ?: 'lodi';
        //获取当前site下语言
//        $langDirArr       = scandir(__DIR__.$this->langDirPath.$site_type);
        $langDirArr       = scandir(__DIR__.$this->langDirPath);
        if (!$langDirArr) return [];
        unset($langDirArr[0],$langDirArr[1]);
        $lang_list     = array_values($langDirArr);
        $lang['list'] = [];
        foreach($lang_list as $key => $val){
            if(in_array($val,['lodi','mxn','ncg'])){
                continue;
            }
            $lang['list'][] = $val;
        }
        //根据site返回默认语言
        $lang['default'] = self::LANG[$site_type];

        return $this->lang->set(0, [], $lang);
    }

};
