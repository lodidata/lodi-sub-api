<?php
require_once __DIR__ . '/../../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../../config/settings.php';
$alias = 'vndMessageServer';

\Workerman\Worker::$logFile = LOG_PATH . '/php/messageSever.log';
$worker = new \Workerman\Worker();
$worker->count = 18;
$worker->name = $alias;

// 防多开配置
// if ($app->getContainer()->redis->get(\Logic\Define\CacheKey::$perfix['prizeServer'])) {
//     echo 'prizeServer服务已启动，如果已关闭, 请等待5秒再启动', PHP_EOL;
//     exit;
// }

$worker->onWorkerStart = function ($worker) {
    global $app, $logger;
    /**********************config start*******************/
    $settings = require __DIR__ . '/../../config/settings.php';
    if (defined('ENCRYPTMODE') && ENCRYPTMODE) {
        $settings['settings'] = \Utils\Utils::settleCrypt($settings['settings'], false);
    }
    $app = new \Slim\App($settings);
    // Set up dependencies
    require_once __DIR__ . '/../src/dependencies.php';

    // Register middleware
    require_once __DIR__ . '/../src/middleware.php';

    require_once __DIR__ . '/../src/common.php';

    $app->run();
    $app->getContainer()->db->getConnection('default');
    $logger = $app->getContainer()->logger;
    /**********************config end*******************/


    $proccId = 0;
    // 写入首页中奖人数值   1
    if ($worker->id === $proccId) {
        $interval = 20 * 60;
        \Workerman\Lib\Timer::add($interval, function () {
            global $app;
            $block = new \Logic\Block\Block($app->getContainer());
            $block->createHomePagePrizeData();
        });
    }


    $proccId++;
    // 返佣模块   2
    if ($worker->id === $proccId) {
        $interval = 20 * 60; //每20分钟执行一次
        \Workerman\Lib\Timer::add($interval, function () {
            global $app;
            $config = \Logic\Set\SystemConfig::getModuleSystemConfig('rakeBack');
            if(isset($config['bkge_open']) && $config['bkge_open'] === false){
                $app->getContainer()->logger->error("【未开启代理返佣】");
                return;
            }
            $bkge = new \Logic\User\Bkge($app->getContainer());
            $redis = $app->getContainer()->redis;
            $bkgeDate = $redis->get(\Logic\Define\CacheKey::$perfix['bkge_date']);
            $day = '01';
            $hour = '12';

            //每周一 12点开始返佣
            if ($bkgeDate == 'week' && date('w') == 1 && $hour == date("H")) {
                $app->getContainer()->logger->debug("【开始周代理返佣】");
                $bkge->newBkgeRunData(null, $bkgeDate);
            }


            //每月1号 12点开始返佣
            if ($bkgeDate == 'month' && $day == date('d') && $hour == date('H')) {
                $app->getContainer()->logger->debug("【开始月代理返佣】");
                $bkge->newBkgeRunData(null, $bkgeDate);
            }

            //是否开启三级返佣
            if(isset($config['bkge_open_third']) && $config['bkge_open_third'] === true){
                $hour = '04';
                if ($hour == date('H')) {
                    $app->getContainer()->logger->debug("【开始个人返佣】");
                    $bkge->runData();  // 系统返佣
                }
            }

            //是否开启全民股东返佣
            if(isset($config['bkge_open_unlimited']) && $config['bkge_open_unlimited'] === true){
                $hour = '04';
                if ($hour == date('H')) {
                    $app->getContainer()->logger->debug("【开始全民股东返佣】");
                    $bkge->unlimitedAgentBkgeRun();  //
                    $app->getContainer()->logger->debug("【全民股东返佣完成】");
                }
            }

            //是否开启盈亏返佣  （盈亏返佣和全民股东返佣只能开启一个）
            if(($config['bkge_open_unlimited'] ?? false) == false && isset($config['bkge_open_loseearn']) && $config['bkge_open_loseearn'] === true){
                //结算方式  1日 2周 3月
                $bkge_settle_type = $config['bkge_settle_type'];
                $hour = '04';
                if ($hour == date('H')) {
                    $app->getContainer()->logger->debug("【开始代理盈亏返佣】");
                    $bkge->agentLoseearnBkgeRun();  //
                    $app->getContainer()->logger->debug("【代理盈亏返佣完成】");

                    //周结
                    if($bkge_settle_type == 2 && date('w') == 1){
                        $bkge->agentLoseearnBkgeWeekStart();
                    }
                    //月结
                    if($bkge_settle_type == 3 && date('d') == '01'){
                        $bkge->agentLoseearnBkgeMonthStart();
                    }
                }

            }
            //佣金活动  暂时只按日反
//            $time = \DB::table('active_bkge')->first(['bkge_date','bkge_time']);
//            $hour = '05';
//            if ($hour == date('H')) {
//                return; //2022-02-15 禁止团队返佣
//                $lock = $app->getContainer()->redis->setnx('user_bkge_active_settle_supervene',1);
//                $app->getContainer()->redis->expire('user_bkge_active_settle_supervene', 5*60);
//                if(!$lock) {
//                    echo 'lock';
//                    return;
//                }
//                $date = date('Y-m-d');
//                $isAllowRun = $app->getContainer()->redis->hget(\Logic\Define\CacheKey::$perfix['runBkgeActive'], $date);
//                if($isAllowRun) {
//                    $app->getContainer()->redis->del('user_bkge_active_settle_supervene');
//                    return;
//                }
//                $app->getContainer()->logger->debug("【开始团队返佣】");
//                $sdate = $edate = date('Y-m-d',strtotime('-1 day'));
//                $bkge->bkgeActiveData($sdate,$edate);  //计算返佣
//                $bkge->bkgeActive();  //
//                $app->getContainer()->redis->hset(\Logic\Define\CacheKey::$perfix['runBkgeActive'], $date, 1);
//                $app->getContainer()->redis->del('user_bkge_active_settle_supervene');
//            }
        });
    }

    $proccId++;
    // 查询代付结果   3
    if ($worker->id === $proccId) {
        $interval = 120;
        \Workerman\Lib\Timer::add($interval, function () use (&$app) {
            print_r('start search transfer');
            $app->getContainer()->logger->debug("【查询代付结果】");
            $stime = date('Y-m-d H:i:s', strtotime(" -2 day"));
            $etime = date('Y-m-d H:i:s', time() - 30);

            $ids = \DB::table('transfer_order')
                ->where('status', '=', 'pending')
                ->whereBetween('created', [
                    $stime,
                    $etime,
                ])
                ->pluck('id');

            foreach ($ids as $v) {
                $transfer = new  Logic\Transfer\ThirdTransfer($app->getContainer());
                $transfer->getTransferResult($v);
            }
        });
    }

    $proccId++;
    // 群消息发送   4
    if ($worker->id === $proccId) {
        $exchange = 'user_message';
        $queue = $exchange . '_1';

        $callback = function ($msg) use ($exchange, $queue, $logger, $app) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $params = json_decode($msg->body, true);
                $workerman = new \Logic\Service\Workerman($app->getContainer());
                $workerman->send_group_msg($params);
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    //用户消息发送通知   5
    $proccId++;
    if ($worker->id === $proccId) {
        $exchange = 'user_message_send';
        $tid = $app->getContainer()
            ->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid;
        $callback = function ($msg) use ($exchange, $queue, $logger, $app) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $params = json_decode($msg->body, true);//['rebet_date' => $date]
                $workerman = new \Logic\Service\Workerman($app->getContainer());
                $workerman->send_private_msg($params);
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }
    $proccId++;

    // 消息发送   6
    if ($worker->id === $proccId) {
        $exchange = 'user_level_upgrade';
        $queue = $exchange . '_1';

        $callback = function ($msg) use ($exchange, $queue, $app, $logger) {
            try {
                print_r('----------用户层级----------');
                echo PHP_EOL;
                $params = json_decode($msg->body, true);
                $login_user = new  \Logic\User\User($app->getContainer());
                $user = \Model\User::find($params['user_id']);
                print_r($params);
                echo PHP_EOL;
                if ($user) {
                    $user = $user->toArray();
                    print_r($user['name'] . ':' . $user['ranting']);
                    echo PHP_EOL;
                    print_r($login_user->upgradeLevelMsg((array)$user, null));
                    echo PHP_EOL;
                }
                print_r('----------用户层级----------');
                echo PHP_EOL;
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }
    $proccId++;
    //回水统计计算   7
    if ($worker->id === $proccId) {
        $interval = 20 * 60; //每半小时秒执行一次
        \Workerman\Lib\Timer::add($interval, function () {
            global $app;

            $redis = $app->getContainer()->redis;

            //回水设置的时间(24小时)
            $hour = (int)$redis->get(\Logic\Define\CacheKey::$perfix['rebot_time']);

            if ($hour == date('H')) {
                $app->getContainer()->logger->debug("【开始返水】");
                $date = ''; // $argv在workerman命令行中不起作用
                //彩票返水
                //$rebet = new \Logic\Lottery\Rebet($app->getContainer());
                //$rebet->runByUserLevelRebet($date, $runMode = 'rebet');

                //电子游戏返水
                $app->getContainer()->logger->debug("【开始游戏返水】");
                $rebet = new \Logic\Lottery\RebetThird($app->getContainer());
                $rebet->runByUserLevelRebet($date, $runMode = 'rebet');
            }

            // 月俸禄
            if (date('d') == '01' && $hour > date('H')) {
                $app->getContainer()->logger->debug("【开始月俸禄】");
                //层级的月俸禄模块 @author Taylor 2018-12-28
                $award = new \Logic\Level\Award($app->getContainer());
                $award->monthly_award();
            }
        });
    }


    $proccId++;
    //活动回水统计计算   8
    if ($worker->id === $proccId) {
        $interval = 25 * 60; //25分支秒执行一次
        \Workerman\Lib\Timer::add($interval, function () {
            global $app;

            $redis = $app->getContainer()->redis;

            $week_issue_day = $redis->get(\Logic\Define\CacheKey::$perfix['week_issue_day']);
            $week_issue_time = $redis->get(\Logic\Define\CacheKey::$perfix['week_issue_time']);
            $month_issue_day = $redis->get(\Logic\Define\CacheKey::$perfix['month_issue_day']);
            $month_issue_time = $redis->get(\Logic\Define\CacheKey::$perfix['month_issue_time']);

            //每周一返水
//            if (1 == date('w') && date('H') == date('H', strtotime($week_issue_time))) {
            if ($week_issue_day == date('N') && date('H') == date('H', strtotime($week_issue_time))) {
                $app->getContainer()->logger->debug("【开始周返水】");
                $rebet = new \Logic\Lottery\Rebet($app->getContainer());
                $rebet->runByWeekActivity('week');
            }

            //每月1号返水
           if ('28' == date('d') && date('H') == '12') {
            //if ($month_issue_day == date('j') && date('H') == date('H', strtotime($month_issue_time))) {
                $app->getContainer()->logger->debug("【开始月返水】");
                $rebet = new \Logic\Lottery\Rebet($app->getContainer());
                $rebet->runByWeekActivity('month');
            }
        });
    }

    $proccId++;
    //抽奖次数清零 9
    if($worker->id === $proccId){
        $interval=1 * 60;
        \Workerman\Lib\Timer::add($interval,function (){
            if(date("H") == '00'){
            global $app;
            $wallet=new Logic\Wallet\Wallet($app->getContainer());
            $wallet->resetLuck();
            }
        });
    }

    $proccId++;
    //后台首页第三部分统计 10
    if ($worker->id === $proccId) {
        $interval = 600; //十分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            global $app;
            $index = new Logic\Admin\AdminIndex($app->getContainer());
            //更新 次日留存 活跃用户留存
            $index->updateNextDayExtant();
            //首页数据 3点执行一次
            if(date("H") == '03'){
                $index->makeThird();
            }
        });
    }



    $proccId++;
    //游戏分类奖励计算 11
    if($worker->id === $proccId){
        $interval=5 * 60;
        \Workerman\Lib\Timer::add($interval,function (){
            if(date("H") == '04'){
                global $app;
                $gameapi = new Logic\Lottery\Rebet($app->getContainer());
                $gameapi->sendGameTypeDataTstat();
            
            }
        });
    }


    //批量赠送彩金任务 12
    $proccId++;
    if($worker->id === $proccId){
        $interval = 60;    // 每分钟执行一次
        \Workerman\Lib\Timer::add($interval,function (){
            global $app;
            $gameapi = new Logic\Lottery\BatchLottery($app->getContainer());
            $gameapi->batchSendLottery();
        });
    }

    $proccId++;
    //推广活动统计发放 13
    if($worker->id === $proccId){
        $interval=5 * 60;
        \Workerman\Lib\Timer::add($interval,function (){
            if(date("H") == '12'){
                global $app;
                $gameapi = new Logic\Lottery\Rebet($app->getContainer());
                $gameapi->sendSpreadTypeDataTstat();

            }
        });
    }

    $proccId++;
    //电访客服更新统计 14
    if($worker->id === $proccId){
        $interval=5 * 60;
        \Workerman\Lib\Timer::add($interval,function (){
            if(date("H") == '05'){
                global $app;
                $gameapi = new Logic\Lottery\Rebet($app->getContainer());
                $gameapi->updateKefuTime();

            }
        });
    }

    //批量赠送彩金任务-非固定模式 15
    $proccId++;
    if($worker->id === $proccId){
        $interval = 60;    // 每分钟执行一次
        \Workerman\Lib\Timer::add($interval,function (){
            global $app;
            $gameapi = new Logic\Lottery\BatchLottery2($app->getContainer());
            $gameapi->batchSendLottery2();
        });
    }

    $proccId++;
    //充值活动 16
    if($worker->id === $proccId){
        $interval=5 * 60;
        \Workerman\Lib\Timer::add($interval,function (){
            global $app;

            $redis = $app->getContainer()->redis;
            $recharge_week_issue_time = $redis->get(\Logic\Define\CacheKey::$perfix['recharge_week_issue_time']);
            $recharge_week_issue_day = $redis->get(\Logic\Define\CacheKey::$perfix['recharge_week_issue_day']);
            if(!empty($recharge_week_issue_day) && !empty($recharge_week_issue_time) && $recharge_week_issue_day == date('N') && date('H') == date('H', strtotime($recharge_week_issue_time))){
                $gameapi = new Logic\Lottery\Rebet($app->getContainer());
                $gameapi->chargeActivity('week');
            }

            if(date("H") == '04'){
                $gameapi = new Logic\Lottery\Rebet($app->getContainer());
                $gameapi->chargeActivity('other');

            }
        });
    }

    $proccId++;
    // 代理盈亏固定占比开关  17
    if ($worker->id === $proccId) {
        $exchange = 'profit_loss_switch';
        $queue = $exchange . '_1';

        $callback = function ($msg) use ($exchange, $queue, $app, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $params = json_decode($msg->body, true);
                $agent=new \Logic\User\Agent($app->getContainer());
                $agent->profitLossSwitch($params);

            } catch (\Exception $e) {
                $logger->error($msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    $proccId++;
    // 队列 APP首次登录赠送 18
    if ($worker->id === $proccId) {
        $exchange = 'synAppLoginPrice';
        $tid = $app->getContainer()->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid;
        $callback = function ($msg) use ($exchange, $queue, $app,  $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $params = json_decode($msg->body, true);
                //APP首次登录赠送
                $activity = new \Logic\Activity\Activity($app->getContainer());
                $activity->sendAppLogin($params['uid'], $params['uuid'], $params['origin']);
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

};
\Workerman\Worker::runAll();
