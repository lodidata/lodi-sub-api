<?php
/**
 * 回水
 */

if (count($argv) > 3) {
    echo "参数不合法\n\r";
    return;
}

$dateType='month';
switch($dateType) {
    case "month":
        $deal_type = 703;    //701-日回水，702-周回水，703-月回水
        $startTime = date("Y-m-01");
        $endTime   = date('Y-m-27');
        $activityType=9;
        $dateStr='月';

        break;
    case "week":
    default :
        //获取周一的时间
        if(date('w') == 1){
            $startTime = date("Y-m-d", strtotime('last monday'));
        }else{
            $startTime = date("Y-m-d", strtotime('-1 week last monday'));
        }

        $deal_type = 702;    //701-日回水，702-周回水，703-月回水
        $endTime   = date('Y-m-d', strtotime("-1 sunday",time()));
        $activityType=8;
        $dateStr='周';
        break;
}
$date     = date('Y-m-d H:i:s', time());
$activity = \DB::table("active")
    ->where('type_id', '=', $activityType)
    ->where('status', '=', "enabled")
    ->where('begin_time', '<', $date)
    ->where('end_time', '>', $date)
    ->first(['id', 'name', 'type_id']);
if(empty($activity)) {
    var_dump("暂无{$dateType}返水活动");
    return false;
}
$rule = \DB::table("active_rule")->where("template_id", '=', $activity->type_id)->where("active_id", '=', $activity->id)->first(['id', 'issue_time', 'issue_cycle', 'issue_mode', 'rule']);
if(empty($rule) || empty($rule->rule)) {
    var_dump("{$activity->name}活动暂未配置规则");
    return false;
}

$activityCnt=\DB::table('active_apply')
    ->where('active_id','=',$activity->id)
    ->where('apply_time','>=',$endTime)
    ->count();
if($activityCnt > 0){
    var_dump('【返水】已经计算过返水数据 ' . $date);
    return false;
}

/**
 * 取规则的值
 * type:betting 按照当日投注额回水；type:loss按照当日亏损额回水
 * status :fixed 按固定金额,percentage 按百分比
 */
$ruleData = json_decode($rule->rule, true);
if(empty($ruleData)) {
    var_dump("{$activity->name}活动暂未配置规则");
    return false;
}
$sTime=microtime(true);
var_dump('游戏返水开始时间'.$sTime);
$rebet = new \Logic\Lottery\Rebet($app->getContainer());
$money = $rebet->getUserActivityBkgeMoney(330586, $startTime, $endTime, $ruleData, $activityType==9?'month':'week');
echo $money;
die;

