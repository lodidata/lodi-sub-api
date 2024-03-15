<?php

use Logic\Admin\BaseController;

/**
 * 获取当前游戏的开关信息
 *
 */
return new class() extends BaseController
{

    const TITLE = '游戏列表';
    const DESCRIPTION = '获取代理包统计信息';
    
    const QUERY = [
        'type' => 'string(, 30) # child or father  默认 为 all',
    ];

    const PARAMS = [];
    const SCHEMAS = [
            [
                'id'        => 'string APP名称',
                'name'    => 'string 渠道',
                'pid' => 'string #渠道ID',
                'childrens'      => []//并不一定有，,
            ]
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $type = $this->request->getParam('type','all');
        switch ($type) {
            case 'child' : $query = \Model\Admin\GameMenu::where('pid','!=',0);break;
            case 'father' : $query = \Model\Admin\GameMenu::where('pid','=',0);break;
        };
        if(isset($query)) {
            return $query->get(['id','pid','name','type'])->toArray();
        }
        $data = \Model\Admin\GameMenu::get(['id','pid','name','type'])->toArray();
        $newArr = array();
        foreach ($data as $item) {
            $item = (array)$item;
            if ($item['pid'] == 0) {
                $newArr[$item['id']] = $item;
            } else {
                if (isset($newArr[$item['pid']])) {
                    $newArr[$item['pid']]['childrens'][] = $item;
                    $newArr[$item['pid']]['childrens'] = array_values($newArr[$item['pid']]['childrens']);
                }
            }
        }
        $newArr = array_values($newArr);
        return $newArr;
    }
};
