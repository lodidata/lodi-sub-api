<?php
/**
 * Created by PhpStorm.
 * User: liluming
 * Date: 2017/11/2
 * Time: 15:08
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
	const TITLE = '取消手动开奖';
	const DESCRIPTION = '';
	
	const PARAMS = [
		'lottery_id' => 'int(required) #自开型彩种ID',
		'lottery_number' => 'int() #彩期ID',
		'type' => 'string() #彩种类型',
	];
	const SCHEMAS = [
		[
		],
	];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

	public function run()
	{
        (new BaseValidate([
            ['lottery_id','require|isPositiveInteger'],
            ['type','require'],
            ['lottery_number','require'],
        ],
            [],
            ['lottery_id'=>'彩票ID','lottery_number'=>'彩期','type'=>'类型']
        ))->paramsCheck('',$this->request,$this->response);

		$req = $this->request->getParams();
		$lottery_id = $req['lottery_id'];
		$lottery_number = $req['lottery_number'];
		$type = $req['type'];

        $table = 'open_lottery_'.$type.'_'.$lottery_id;
        $now = time();  //在时间内可取消手动
        $end_time = \DB::table("$table")->where('lottery_number',$lottery_number)->value('end_time');
        if ($now <= $end_time) {
            $res = \DB::table("$table")->where('lottery_number',$lottery_number)->update(['period_code'=>'']);
            if($res !== false){
                return $this->lang->set(0);
            }
            return $this->lang->set(10573);
        }
        return $this->lang->set(10573);
    }
};