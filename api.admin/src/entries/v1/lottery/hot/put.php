<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Updated By: Jacky.Zhuo <zhuojiejie@funtsui.com>
 * Date: 2018/6/7
 * Time: 16:18
 */

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\Admin\HotLottery;

return new class() extends BaseController
{
    const TITLE = '首页彩种设定';

    const DESCRIPTION = '展示到首页的彩种排序(首页排序分为两个时间段)';

    const QUERY = [];
    const PARAMS = [
            [
                'id' => 'integer #彩种id',
                'statue' => 'integer #状态 0关闭 1启用',
                'sort' => 'integer #排序',
            ]
    ];

    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        //    id，state，sort
        //    $upData = [
        //        ['id'=>1,'state'=>1,'sort'=>1],
        //        ['id'=>2,'state'=>1,'sort'=>1],
        //        ['id'=>3,'state'=>1,'sort'=>1],
        //    ];

        $params = $this->request->getParsedBodyParam('data');
        if ($params) {
            $data= DB::table('hot_lottery')
                ->where('id','=',$params[0]['id'])
                ->get()
                ->first();
            $data=(array)$data;
            $res = (new HotLottery())->updateBatch($params);
            if ($res === false) {
                (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '彩种设定', '首页彩种排序', '编辑', 0, "时间段：{$data['timeType']}");
                return $this->lang->set(-2);
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '彩种设定', '首页彩种排序', '编辑', 1, "时间段：{$data['timeType']}");
        } else {
            return $this->lang->set(10010);
        }
    }
};