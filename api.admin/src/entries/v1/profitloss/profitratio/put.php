<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/7 13:04
 */

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '修改盈亏返佣比例';
    const DESCRIPTION = '修改盈亏返佣比例';
    
    const QUERY = [
    ];
    
    const PARAMS = [

    ];
    const STATEs = [
//        \Las\Utils\ErrorCode::NO_PERMISSION => '无权限操作'
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $param = $this->request->getParams();

        $type = $param['type'] ?? '';
        $ratio = $param['ratio'] ?? -1;
        $is_take = $param['is_take'] ?? -1;

        if(empty($type) || !is_numeric($ratio) || $ratio < 0 || !in_array($is_take,[0,1])){
            return $this->lang->set(-2);
        }

        //获取参与比例配置
        $profit_ratio = DB::table('system_config')->where('module','profit_loss')->where('key','profit_ratio')->first();
        $ratio_list = [];
        if(!empty($profit_ratio) && !empty($profit_ratio->value)){
            $ratio_list = json_decode($profit_ratio->value, true);
        }
        $ratio_list[$type]['ratio'] = $ratio;
        $ratio_list[$type]['is_take'] = $is_take;

        $update = [
            'value' => json_encode($ratio_list),
        ];
        try{
            DB::table('system_config')->where('module','profit_loss')->where('key','profit_ratio')->update($update);
        } catch (\Exception $e) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }
};
