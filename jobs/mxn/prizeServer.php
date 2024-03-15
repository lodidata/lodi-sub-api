<?php
require_once __DIR__ . '/../../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../../config/settings.php';
$alias = 'mxnPrizeServer';

\Workerman\Worker::$logFile = LOG_PATH.'/php/prizeServer.log';
$worker = new \Workerman\Worker();
$worker->count = 19;
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
    if(defined('ENCRYPTMODE') && ENCRYPTMODE) {
        $settings['settings'] = \Utils\Utils::settleCrypt($settings['settings'],false);
    }
    $app = new \Slim\App($settings);
    // Set up dependencies
    require_once __DIR__ . '/../src/dependencies.php';

    // Register middleware
    require_once __DIR__ . '/../src/middleware.php';

    $app->run();
    $app->getContainer()->db->getConnection('default');
    $logger = $app->getContainer()->logger;
    /**********************config end*******************/



    $lottery = \Logic\Lottery\RsyncLotteryInfo::init();

    $proccId = 0;
    if ($worker->id === $proccId) {
        // 启动时创建自开奖彩期
        $logger->info('开始生成彩期');
        \Logic\Lottery\OpenPrize::openPrizeCreateLottery($lottery);
        $logger->info('自开奖彩期生成结束');

        // 启动时从平台同步一次彩期
       // \Logic\Lottery\RsyncLotteryInfo::rsyncCreateLottery($lottery);
        $logger->info('同步彩期生成结束');

        // 启动时默认先加载一次彩期
        $logger->info('刷新彩期');
        \Logic\Lottery\RsyncLotteryInfo::loadLotteryInfo($lottery);

        // 启动时默认先加载一次追号彩期
        $logger->info('追号彩期');
        \Logic\Lottery\RsyncLotteryInfo::writeChaseLotteryInfo($lottery);
    }

    $proccId++;
    // 加载彩期信息  2
    if ($worker->id === $proccId) {
        $interval = 60 * 20;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::loadLotteryInfo($lottery, [
                43,
                52,
            ]);
        });
    }

/*    $proccId++;
    // 加拿大彩期加载六合   生成彩期一般只生成下一期，所以间隔时间短  3
    if ($worker->id === $proccId) {
        $interval = 60;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            //加拿大
            \Logic\Lottery\RsyncLotteryInfo::loadLotteryInfo($lottery, null, 43);
            //六合彩
            \Logic\Lottery\RsyncLotteryInfo::loadLotteryInfo($lottery, null, 52);
        });
    }*/

    $proccId++;
    // 当前彩期和下一期缓存写入   4
    if ($worker->id === $proccId) {
        $interval = 10;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::writeCurrAndNextLotteryInfo($lottery);
        });
    }

    $proccId++;
    // 写入追号期数到缓存 5
    if ($worker->id === $proccId) {
        $interval = 25*60;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::writeChaseLotteryInfo($lottery);
        });
    }

    $proccId++;
    // 定时刷新历史开奖结果 6
    if ($worker->id === $proccId) {
        $interval = 60;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::autoRefreshHistory($lottery);
        });
    }

/*    $proccId++;
    // 追号通知  7
    if ($worker->id === $proccId) {
        $interval = 5;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::chaseNotify($lottery);
        });
    }*/

    $proccId++;
    // 自开奖  8
    if ($worker->id === $proccId) {
        $interval = 5;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\OpenPrize::run($lottery);
        });
    }

    $proccId++;
    // 自开奖彩期生成   9
    if ($worker->id === $proccId) {
        $interval = 3 * 3600;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\OpenPrize::openPrizeCreateLottery($lottery);
        });
    }

/*    $proccId++;
    // 彩期同步   10
    if ($worker->id === $proccId) {
        $interval = 3 * 3600;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::rsyncCreateLottery($lottery);
        });
    }*/

  /*  $proccId++;
    // 加拿大 & 六合彩 彩期同步    11
    if ($worker->id === $proccId) {
        $interval = 1 * 60;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            // 加拿大
            \Logic\Lottery\RsyncLotteryInfo::rsyncCreateLottery($lottery, $id = 43);

            // 六合彩
            \Logic\Lottery\RsyncLotteryInfo::rsyncCreateLotteryLhc($lottery);
        });
    }

    $proccId++;
    // 官开-开奖结果同步   12
    if ($worker->id === $proccId) {
        $interval = 5;
        // $interval = 3;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::run($lottery);
        });
    }

    $proccId++;
    // 官开-全量同步开奖结果   13
    if ($worker->id === $proccId) {
        $interval = 2 * 60;
        \Workerman\Lib\Timer::add($interval, function () use (&$lottery) {
            \Logic\Lottery\RsyncLotteryInfo::allprize($lottery);
        });
    }*/

    $proccId++;
    // 结算模块1  14
    if ($worker->id === $proccId) {
        $exchange = 'lotterysettle_send_1';
        $tid = $app->getContainer()
                   ->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid . '_' . 'sabc';
        $settle = new \Logic\Lottery\Settle($app->getContainer());
        $callback = function ($msg) use ($exchange, $queue, $settle, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $arr = json_decode($msg->body, true);
                if (is_array($arr)) {
                    $settle->runByNotifyV2($arr['lottery_type'], $arr['lottery_number']);
                    //试玩结算
                    /*\Utils\MQServer::send('lotterysettle_try_play', [
                        'lottery_type'   => $arr['lottery_type'],
                        'lottery_number' => $arr['lottery_number'],
                    ]);*/
                }
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }
    $proccId++;
    // 结算模块2   15
    if ($worker->id === $proccId) {
        $exchange = 'lotterysettle_send_2';
        $tid = $app->getContainer()
            ->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid . '_' . 'sabc';
        $settle = new \Logic\Lottery\Settle($app->getContainer());
        $callback = function ($msg) use ($exchange, $queue, $settle, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $arr = json_decode($msg->body, true);
                if (is_array($arr)) {
                    $settle->runByNotifyV2($arr['lottery_type'], $arr['lottery_number']);
                    //试玩结算
                    /*\Utils\MQServer::send('lotterysettle_try_play', [
                        'lottery_type'   => $arr['lottery_type'],
                        'lottery_number' => $arr['lottery_number'],
                    ]);*/
                }
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }
    $proccId++;
    // 结算模块3   16
    if ($worker->id === $proccId) {
        $exchange = 'lotterysettle_send_3';
        $tid = $app->getContainer()
            ->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid . '_' . 'sabc';
        $settle = new \Logic\Lottery\Settle($app->getContainer());
        $callback = function ($msg) use ($exchange, $queue, $settle, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $arr = json_decode($msg->body, true);
                if (is_array($arr)) {
                    $settle->runByNotifyV2($arr['lottery_type'], $arr['lottery_number']);
                    //试玩结算
                    /*\Utils\MQServer::send('lotterysettle_try_play', [
                        'lottery_type'   => $arr['lottery_type'],
                        'lottery_number' => $arr['lottery_number'],
                    ]);*/
                }
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    $proccId++;
    // 结算模块4   17
    if ($worker->id === $proccId) {
        $exchange = 'lotterysettle_send_4';
        $tid = $app->getContainer()
            ->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid . '_' . 'sabc';
        $settle = new \Logic\Lottery\Settle($app->getContainer());
        $callback = function ($msg) use ($exchange, $queue, $settle, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $arr = json_decode($msg->body, true);
                if (is_array($arr)) {
                    $settle->runByNotifyV2($arr['lottery_type'], $arr['lottery_number']);
                    //试玩结算
                    /*\Utils\MQServer::send('lotterysettle_try_play', [
                        'lottery_type'   => $arr['lottery_type'],
                        'lottery_number' => $arr['lottery_number'],
                    ]);*/
                }
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    /*$proccId++;
    // 试玩结算模块   18
    if ($worker->id === $proccId) {
        $exchange = 'lotterysettle_try_play';
        $tid = $app->getContainer()
                   ->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid . '_' . 'sabc';
        $settle = new \Logic\Lottery\Settle($app->getContainer());
        $callback = function ($msg) use ($exchange, $queue, $settle, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $arr = json_decode($msg->body, true);
                if (is_array($arr)) {
                    //试玩结算
                    $settle->runByTrialNotifyV2($arr['lottery_type'], $arr['lottery_number']);
                }
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        try{
            \Utils\MQServer::startServer($exchange, $queue, $callback);
        }catch (\Exception $e){
            $logger->info('rabbitmq:'.json_encode($e->getTrace()));
        }

    }*/

/*    $proccId++;
    // 追号模块   19
    if ($worker->id === $proccId) {
        $chase = new \Logic\Lottery\ChaseOrder($app->getContainer());
        $tid = $app->getContainer()
                   ->get('settings')['app']['tid'];
        $exchange = 'lottery_start_point';
        $queue = $exchange . '_' . $tid . '_' . 'sabc';
        $callback = function ($msg) use ($exchange, $queue, $chase, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $arr = json_decode($msg->body, true);
                if (is_array($arr)) {
                    $chase->runByNotify($arr['lottery_type'], $arr['lottery_number']);
                    //试玩追号
                    $chase->runTrialByNotify($arr['lottery_type'], $arr['lottery_number']);
                }
            } catch (\Exception $e) {
                $logger->error($msg->body);
                // $logger->error("【$exchange,$queue】error:".$e->getFile().':'.$e->getLine().':'.$e->getMessage());
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }*/

    $proccId++;
    // 补结算   20
    if ($worker->id === $proccId) {
        $interval = 60;
        \Workerman\Lib\Timer::add($interval, function () {
            global $app;
            $settle = new \Logic\Lottery\Settle($app->getContainer());
            $settle->runReopenV2();
        });
    }


/*    $proccId++;
    // 补追号结算   21
    if ($worker->id === $proccId) {
        $interval = 60;
        \Workerman\Lib\Timer::add($interval, function () {
            global $app;
            $chase = new \Logic\Lottery\ChaseOrder($app->getContainer());
            $chase->runReopen();
        });
    }*/

    $proccId++;
    // 补自开奖    22
    if ($worker->id === $proccId) {
        $interval = 60;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\Lottery\OpenPrize::runReopen();
        });
    }
};
\Workerman\Worker::runAll();