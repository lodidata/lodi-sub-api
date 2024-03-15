<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '更新系统设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        // 配置模块
        $module = ['register'=>'','registerCon'=>'','rakeBack'=>'','market'=>'','market2'=>'','activity'=>'','login'=>'','audit'=>'','system'=>'','agent'=>'','admin_pin_password'=>'','domain'=>'','network_safty'=>'','realTimeRebate'=>'','kagame'=>''];
        $data = $this->request->getParams();
        unset($data['s']);
        $systen_config = \Logic\Set\SystemConfig::getGlobalSystemConfig();
        $tmp = array_intersect_key($systen_config,$module);
        $confg = new \Logic\Set\SystemConfig($this->ci);
        $t = [];
        if(isset($data['registerCon'])) {
            foreach ($data['registerCon'] as $val) {
                $t[$val['key']] = $val;
            }
            $data['registerCon'] = $t;
        }
        $data['rakeBack'] = array_merge($data['rakeBack'], $data['rakeBack1'], $data['rakeBack2']);
        unset($data['rakeBack1'], $data['rakeBack2'], $data['system']['smsList']);
        //过滤出新增的h5推广链接
        if (isset($data['new_market']) && !empty($data['new_market'])) {
            $new_h5 = [];    //保存需要新增的h5_url
            $del_ht = [];    //保存需要删除的h5_url
            foreach ($data['new_market'] as $v) {
                if (isset($v['is_new']) && $v['is_new'] == 1) {
                    array_push($new_h5, $v['value']);
                } elseif (isset($v['is_new']) && $v['is_new'] == -1) {
                    array_push($del_ht, $v['key']);
                } else {
                    $data['market'][$v['key']] = $v['value'];   //替换原有的h5_url
                }
                //新增h5_url数据
                if (!empty($new_h5)) {
                    foreach ($new_h5 as $value) {
                        $name = 'H5推广';
                        $tmp_key = "h5_url_".time();
                        $insertId = DB::table('system_config')->insertGetId([
                            'module' => 'market',
                            'name' => $name,
                            'key' => $tmp_key,
                            'type' => 'string',
                            'value' => trim($value),
                            'desc' => "",
                            'state' => "enabled"
                        ]);
                        if ($insertId) {
                            $newName = $name.intval($insertId);
                            $new_key = "h5_url_".intval($insertId);
                            DB::table('system_config')->where('id','=', intval($insertId))->update(['name'=>$newName,'key'=>$new_key]);
                        }
                    }
                    $this->redis->del(\Logic\Set\SystemConfig::SET_GLOBAL);
                }
                //删除h5_url
                if (!empty($del_ht)) {
                    DB::table('system_config')->whereIn('key',$del_ht)->delete();
                    $this->redis->del(\Logic\Set\SystemConfig::SET_GLOBAL);
                }
            }
            unset($data['new_market']);
        }
        $confg->updateSystemConfig($data,$tmp);
        if(isset($data['rakeBack']) && $data['rakeBack']['agent_switch'] !== $tmp['rakeBack']['agent_switch']) {  //所有用户
            if($data['rakeBack']['agent_switch']) {
                \DB::table('user')->update(['agent_switch'=>1]);
            }else {
                \DB::table('user')->update(['agent_switch'=>0]);
            }
        }
        return $this->lang->set(0);
    }
};
