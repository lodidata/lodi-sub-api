<?php
/**
 * 新增会员层级
 * @author Taylor 2019-01-10
 */
use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Logic\Level\Level as levelLogic;
use Model\Admin\UserLevel;
return new class() extends BaseController
{
    const TITLE       = '新增等级';
    const DESCRIPTION = '新增等级';
    const PARAMS       = [
        'name' => 'string #等级名称',
        'deposit_money' => 'int #最低充值金额',
        'online_dml_percent' => 'float #线上充值打码量',
        'offline_dml_percent' => 'float #线下充值打码量',
        'level' => 'int #数字等级',
        'icon' => 'string #等级图标URL地址',
        'lottery_money' => 'int #最低投注量',
        'user_count' => 'int #该层级对应的会员人数',
        'upgrade_dml_percent' => 'float #提现打码量',
        'draw_count' => 'int #活动免费抽奖次数',
        'promote_handsel' => 'int #晋升彩金',
        'transfer_handsel' => 'int #转卡彩金',
        'monthly_money' => 'int #月俸禄彩金，分为单位',
        'monthly_percent' => 'int #月俸禄条件百分比',
        'online'=> 'string #层级对应的线上支付列表',
        'offline'=> 'string #层级对应的线下支付列表',
        'bankcard_sum' => 'int #银行卡绑定数',
    ];
    const SCHEMAS     = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
       //(new LevelValidate())->paramsCheck('post',$this->request,$this->response);
        $params = $this->request->getParams();
        //判断是否是系统默认会员等级
        if($params['level'] == 1){
            return $this->lang->set(10901);
        }
        //判断条件等级是否重复
        $before_value = DB::table('user_level')->where('level',$params['level']-1)->select('id','lottery_money','deposit_money')->get()->toArray();
        if ($params['welfare']['withdraw_max'] > $params['welfare']['daily_withdraw_max'] && $params['welfare']['daily_withdraw_max'] != 0)  return $this->lang->set(11067);
        if ($params['welfare']) $params['welfare'] = json_encode($params['welfare']);
        if(!empty($before_value)){
            $levels = $params['level'] - 1;
            if($params['lottery_money'] <= $before_value[0]->lottery_money || $params['deposit_money'] <= $before_value[0]->deposit_money){
                return $this->lang->set(10902, [$levels]);
            }
        }
        //判断是否越级
        $level_value = DB::table('user_level')->count('id');
        if($params['level'] != $level_value+1){
            return $this->lang->set(10902,[$level_value]);
        }
        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '会员等级', '管理等级', '新增等级', 1, "等级名称：{$params['name']}");
        /*============================================================*/
        return $this->addLevel($params);
    }

    //新增层级
    protected function addLevel($params){
        $levelModel = new UserLevel();
        $levelModel->name = $params['name'];
        $levelModel->deposit_money = $params['deposit_money'];
        $levelModel->offline_dml_percent = $params['offline_dml_percent'];
        $levelModel->online_dml_percent = $params['online_dml_percent'];
        $levelModel->level = $params['level'];
        $levelModel->icon = replaceImageUrl($params['icon']);
        $levelModel->level_background = replaceImageUrl($params['level_background']);
        $levelModel->background       = replaceImageUrl($params['background']);
        $levelModel->lottery_money = $params['lottery_money'];
        $levelModel->upgrade_dml_percent = $params['upgrade_dml_percent'];
        $levelModel->promote_handsel = $params['promote_handsel'];
        $levelModel->transfer_handsel = $params['transfer_handsel'];
        $levelModel->draw_count = $params['draw_count'];//活动免费抽奖次数
        $levelModel->monthly_money = isset($params['monthly_money']) ?$params['monthly_money']:0;//月俸禄金额，以分为单位
        $levelModel->monthly_percent = isset($params['monthly_percent'])?$params['monthly_percent']:0;//月俸禄达到晋升投注量的百分比，需要乘以100
        $levelModel->monthly_recharge = isset($params['monthly_recharge'])?$params['monthly_recharge']:0;
        $levelModel->bankcard_sum = $params['bankcard_sum'] ?? 1;
        $levelModel->week_money   = $params['week_money'];
        $levelModel->welfare      = $params['welfare'];
        $levelModel->fee          = $params['fee'];
        $levelModel->week_recharge= $params['week_recharge'];
        $levelModel->split_line = replaceImageUrl($params['split_line']);
        $levelModel->font_color = $params['font_color'];
        //设置周薪发放时间
        $this->redis->set(\Logic\Define\CacheKey::$perfix['week_award_day'], $params['week_award_day']);//设置回水时间
        $this->redis->set(\Logic\Define\CacheKey::$perfix['week_award_time'], $params['week_award_time']);//设

        DB::beginTransaction();
        try{
            $levelModel->save();
            $levelId = $levelModel->id;
            $levelLogic = new levelLogic($this->ci);
            //更新层级对应的线上层级渠道

            if(isset($params['online']) && count($params['online'])){
                $onlineData = [];
                foreach ($params['online'] ??[] as $k=>$v){
                    $onlineData[] = ['level_id'=>$levelId,'pay_plat'=>$v];
                }
                $levelLogic->onlineSet($levelId,$onlineData);
            }
            //更新层级对应的线下层级渠道
            if(isset($params['online']) && count($params['offline'])){
                $offlineData = [];
                foreach ($params['offline'] ??[] as $k=>$v){
                    $offlineData[] = ['level_id'=>$levelId,'pay_id'=>$v];
                }
                $levelLogic->offlineSet($levelId,$offlineData);
            }
            DB::commit();
            return $this->lang->set(0);
        }catch (\Exception $e){
            DB::rollback();
            return $this->lang->set(10404);
        }
        return $this->lang->set(0);
    }
};
