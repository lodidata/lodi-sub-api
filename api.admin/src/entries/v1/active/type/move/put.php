<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Utils\Utils;
use Model\ActiveType;

return new class() extends BaseController
{
    const TITLE = '更新活动分类排序';
    const PARAMS = [
        "data" => [],
        "id" => "分类id",
        "sort" => "分类排序值",
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $params = $this->request->getParsedBodyParam('data');
        //$params=[["id"=>1,"sort"=>1],["id"=>4,"sort"=>3],...];

        if ($params) {
            $res = Utils::updateBatch($params, 'active_type');

            if ($res!==false) {
                //更新缓存
                $data = ActiveType::where('status', '=', 'enabled')->orderby('sort', 'asc')->select(['id', 'name', 'description', 'created'])->get()->toArray();
                $data = DB::resultToArray($data);
                $data = array_merge([["id" => "0", "name" => $this->lang->text("All activities"), "description" => "", "created" => ""]], $data);
                if($data){
                    $this->redis->setex(\Logic\Define\CacheKey::$perfix['activeTypes'], 30, json_encode($data, true));
                }

                (new Log($this->ci))->create(null, null, Log::MODULE_ACTIVE, '活动管理', '活动排序设置', '编辑', 1, "");
                return $this->lang->set(0);
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_ACTIVE, '活动管理', '活动排序设置', '编辑', 0, "");
            return $this->lang->set(-2);
        } else {
            return $this->lang->set(10010);
        }
    }

};
