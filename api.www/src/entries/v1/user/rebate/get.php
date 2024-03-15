<?php

use Utils\Www\Action;

return new class() extends Action {
    
	protected function getResultField()
	{
		return [
        	'total' 	=> 0,
        	'next_up'	=> [
                'day' => 0,
                'week' => 0,
                'month' => 0,
                'day_same' => true,
                'week_same' => true,
                'month_same' => true,
            ],
        	'detail'	=> [
        		'current'  => [
        			'day'	=> 0,
        			'week'	=> 0,
        			'month' => 0,
        		],
        		'next' => [
        			'day'	=> 0,
        			'week'	=> 0,
        			'month' => 0,
        		],
        	],
        ];
	}

    public function run()
    {
        // 鉴权
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $user_id = $this->auth->getUserId();
        $gid = $this->request->getParam('gid', 0);
        if (empty($user_id) || empty($gid)) {
            return $this->lang->set(10, [], [], []);
        }

        // 返回列
        $res = $this->getResultField();

        $rebet = new \Logic\Lottery\UserBackWater($this->ci);

        // 日返
        $res['day']   = $rebet->getUserRebetDetailByDaily($user_id, $gid);
        // 周返
        $res['week']  = $rebet->getUserRebetDetailByWeekOrMonth($user_id, $gid, 'week');
        // 月返
        $res['month'] = $rebet->getUserRebetDetailByWeekOrMonth($user_id, $gid, 'month');

        // 下个直推等级提升后比例
        $direct = $rebet->getUserDirectProgress($user_id);
        if(!empty($direct['next'])){
//        	$res['detail']['next']['day'] = sprintf('%.2f', $res['day']['base']  * (1 + (intval($direct['next']['bkge_increase']) / 100)));
//        	$res['detail']['next']['week'] = sprintf('%.2f', $res['week']['base'] * (1 + (intval($direct['next']['bkge_increase_week']) / 100)));
//        	$res['detail']['next']['month'] = sprintf('%.2f', $res['month']['base'] * (1 + (intval($direct['next']['bkge_increase_month']) / 100)));
            $res['next_up']['is_top_direct'] = $direct['next']['is_top_direct'] ?? false;
            if(isset($direct['next']['bkge_increase'])){
                $res['next_up']['day'] = $direct['next']['bkge_increase'];
                $res['detail']['next']['day'] = sprintf('%.2f', $res['day']['base']  * (1 + (intval($direct['next']['bkge_increase']) / 100)));
            }else{
                $res['detail']['next']['day'] = sprintf('%.2f', $res['day']['base']  * (1 + (0 / 100)));
            }
            if(isset($direct['next']['bkge_increase_week'])){
                $res['next_up']['week'] = $direct['next']['bkge_increase_week'];
                $res['detail']['next']['week'] = sprintf('%.2f', $res['week']['base'] * (1 + (intval($direct['next']['bkge_increase_week']) / 100)));
            }else{
                $res['detail']['next']['week'] = sprintf('%.2f', $res['week']['base'] * (1 + (0 / 100)));
            }
            if(isset($direct['next']['bkge_increase_month'])){
                $res['next_up']['month'] = $direct['next']['bkge_increase_month'];
                $res['detail']['next']['month'] = sprintf('%.2f', $res['month']['base'] * (1 + (intval($direct['next']['bkge_increase_month']) / 100)));
            }else{
                $res['detail']['next']['month'] = sprintf('%.2f', $res['month']['base'] * (1 + (0 / 100)));
            }
        }

        // 没有打码 返水比例已需加上提升比例
        if(!empty($direct['curr'])) {
            if($res['day']['dml'] == 0) {
                $res['day']['rate'] = sprintf('%.2f', $res['day']['rate']  * (1 + (intval($direct['curr']['bkge_increase']) / 100)));
            }
            if($res['week']['dml'] == 0) {
                $res['week']['rate'] = sprintf('%.2f', $res['week']['rate']  * (1 + (intval($direct['curr']['bkge_increase_week']) / 100)));
            }
            if($res['month']['dml'] == 0) {
                $res['month']['rate'] = sprintf('%.2f', $res['month']['rate']  * (1 + (intval($direct['curr']['bkge_increase_month']) / 100)));
            }
        }

        // 当前比例 包含直推任务提升后
        $res['detail']['current']['day'] = $res['day']['rate'];
        $res['detail']['current']['week'] = $res['week']['rate'];
        $res['detail']['current']['month'] = $res['month']['rate'];
        if (!empty($res['detail']['next'])) {
            $res['next_up']['day_same'] = bccomp($res['detail']['next']['day'], $res['detail']['current']['day'], 2) == 0;
            $res['next_up']['week_same'] = bccomp($res['detail']['next']['week'], $res['detail']['current']['week'], 2) == 0;
            $res['next_up']['month_same'] = bccomp($res['detail']['next']['month'], $res['detail']['current']['month'], 2) == 0;
        }
        $res['total'] = number_format(array_sum($res['detail']['current']), 2);

        return $res;
    }


};
