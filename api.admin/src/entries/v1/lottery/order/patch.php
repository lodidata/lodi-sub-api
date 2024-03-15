<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const TITLE = '彩票设定编辑开关';
    const DESCRIPTION = '';
    
    const QUERY = [
    ];
    
    const PARAMS = [
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null)
    {
        $arrName = [
            'standard',
            'fast',
            'chat',
            'enabled',
            'video',
        ];

        $input = $this->request->getParams();
        $input = (array)$input;

        $data = \DB::table('lottery')
            ->find($id);
        if (!$data) {
            return $this->lang->set(10015);
        }

        //修改开盘延迟
        if(isset($input['start_delay'])) {
            /*==========================管理员操作日志==================================*/
            $lottery = \Model\Admin\Lottery::find($id);
            $lottery->logs_type = '修改开盘延迟';
            $lottery->start_delay = $input['start_delay'];
            $lottery->desc_desc = "彩种名称：{$data->name}";
            $res = $lottery->save();
            if ($res !== false) {
                return $this->lang->set(0);
            }
            return $this->lang->set(-2);
        }
        $params = [];

        foreach ($arrName as $item) {
            if (isset($input[$item])) {
                $params[$item] = $input[$item];
            }
        }

        $state = $data->state;
        $stateArr = [];
        $data->state && ($stateArr = explode(',', $state));


        foreach ($arrName as $name) {
            if (isset($params[$name]) && $params[$name] == 1) {
                $stateArr[] = $name;
            } else if (isset($params[$name]) && $params[$name] == 0) {
                foreach ($stateArr as $k => $v) {
                    if ($v == $name) {
                        unset($stateArr[$k]);
                    }
                }
            }
        }

        $stateArr = array_unique($stateArr);
        $state = implode(',', $stateArr);
        /*==========================管理员操作日志==================================*/
        $lottery = \Model\Admin\Lottery::find($id);
        $lottery->logs_type = '开启/关闭';
        $lottery->state = $state;
        $lottery->desc_desc = "彩种名称：{$data->name}";
        $res = $lottery->save();
        if ($res !== false) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }

};
