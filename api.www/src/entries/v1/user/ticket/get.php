<?php

use Utils\Www\Action;

/**
 * 用户中心的更多：帮助中心、关于我们等
 */
return new class extends Action
{
    const TITLE = "帮助中心-分类列表";
    const TAGS = "帮助中心";
    const SCHEMAS = [
        [
            "id" => "int(required) #ID 1",
            "name" => "string(required) #分类名称 帮助中心",
            "pid" => "int(required) #上级ID 0",
            "img" => "string() #分类图片",
            "desc" => "string() #分类描述",
            "lang" => "string() #语言代码 zh-cn",
            "child" => [
                [
                    "id" => "int(required) #ID 2",
                    "name" => "string(required) #分类名称 常见问题",
                    "pid" => "int(required) #上级ID 1",
                    "img" => "string() #分类图片",
                    "desc" => "string() #分类描述",
                    "lang" => "string() #语言代码 zh-cn",
                    "child" => [
                        [
                            "id" => "int(required) #ID 3",
                            "name" => "string(required) #分类名称 注册与登录",
                            "pid" => "int(required) #上级ID 2",
                            "img" => "string() #分类图片",
                            "desc" => "string() #分类描述",
                            "lang" => "string() #语言代码 zh-cn",
                        ]
                    ]
                ]
            ]
        ]
    ];

    private $menus=[];

    public function run()
    {
//        $verify = $this->auth->verfiyToken();
//        if (!$verify->allowNext()) {
//            return $verify;
//        }

        $data = DB::table('ticket')
            ->select(['id','name','pid','img','desc','lang'])
            ->get()
            ->toArray();
        $this->menus=$data;
        $dataArr=$this->getMenu();

        return $dataArr;
    }



    /**
     * 主菜单pid为0
     * @return array
     */
    protected function getMenu() {
        $menu=[];
        foreach ($this->menus as $key => $items) {
            $items=(array)$items;
            if ($items['pid'] == "0" ) {
                unset($this->menus[$key]);
                $menu[] = $this->buildMenuTree($items, $items['id']);
            }
        }
        return $menu;
    }


    /**
     * 生产多级菜单树
     * @param array $items
     * @param int $rid
     * @return array
     */
    protected function buildMenuTree($items,$rid) {
        $childs = $this->getChildMenu($items, $rid);
        if (isset($childs['child'])) {
            foreach ($childs['child'] as $key => $value) {
                $children = $this->buildMenuTree($value, $value['id']);
                if (isset($children['child'] ) && null != $children['child'] ) {
                    $childs['child'][$key]['child'] = $children['child'];
                }
            }
        }
        return $childs;
    }

    /**
     * 获取子菜单
     *
     */
    protected function getChildMenu($items,$rid) {
        foreach ($this->menus as $key => $value) {
            $value=(array)$value;
            if ($value['pid'] == $rid) {
                unset($this->menus[$key]);
                $items['child'][] = $value;
            }
        }
        return $items;
    }



};