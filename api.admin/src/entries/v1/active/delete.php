<?php

use Logic\Admin\Log;
use Logic\Admin\BaseController;

/**
 * 删除活动
 */
return new class() extends BaseController
{
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);

        /*======================操作日志代码============================*/
        $info = DB::table('active')->select('type_id', 'status', 'name')->where('id', '=', $id)->get()->first();
        $type_info = DB::table('active_template')
            ->find($info->type_id);
        /*============================日志操作代码================================*/
        if ($info->type_id == 6) {
            if ($info->status == 'enabled') {
                return $this->lang->set(10810);
            }
        }
        //判断轮播图是否关联当前活动
        $exists = DB::table('advert')->where('link_type', 2)->where('link', $id)->get()->toArray();
        if ($exists)
            return $this->lang->set(10551);
        //删除
        DB::beginTransaction();
        try {
            $sql = "delete from active where id = {$id}";
            $sql2 = "delete from active_rule where active_id= {$id}";
            DB::delete($sql);
            DB::delete($sql2);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            (new Log($this->ci))->create(null, null, Log::MODULE_ACTIVE, '活动列表', '活动列表', "删除", 0, "活动名称：{$info->name}/活动类型:{$type_info->name}");
            return $this->lang->set(-2);
        }

        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_ACTIVE, '活动列表', '活动列表', "删除", 1, "活动名称：{$info->name}/活动类型:{$type_info->name}");
        /*============================================================*/
        return $this->lang->set(0);
    }

};
