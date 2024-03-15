<?php
require_once __DIR__ . '/../../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../../config/settings.php';
$alias = 'vndGameServer';

\Workerman\Worker::$logFile = LOG_PATH . '/php/gameServer.log';
$worker = new \Workerman\Worker();
$worker->count = 10;
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

     // 第三方订单通知   只传递结算的订单 9
     if ($worker->id === $proccId) {
         $exchange = 'synchronousOrderCallback';
         $tid = $app->getContainer()->get('settings')['app']['tid'];
         $queue = $exchange . '_' . $tid;
         $ci = $app->getContainer();
         $logger = $app->getContainer()->logger;
         $callback = function ($msg) use ($exchange, $queue, $ci, $logger) {
             try {
                 $logger->info("【 $exchange, $queue 】" . $msg->body);
                 //调用对应需要处理的逻辑
                 $callList = (new \Model\Game3th())->orderCallbackList;
                 $arr = json_decode($msg->body, true);
                 foreach ($callList as $class => $func) {
                     if(!class_exists($class)) {
                        continue;
                    }
                    if (!is_callable(array($class, $func))) {
                        continue;
                    }
                    $c = new $class($ci);
                    $c->$func($arr);
                    //call_user_func([$class,$func],$arr);  这样调用 构造函数缺少参数
                }
            } catch (\Exception $e) {
                $logger->error("【 $exchange, $queue 】" . $msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    $proccId++;
    // 十五分彩订单通知3   只传递结算的订单
    if ($worker->id === $proccId) {
        $exchange = 'synchronousOrderCallback_110';
        $tid = $app->getContainer()->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid;
        $ci = $app->getContainer();
        $logger = $app->getContainer()->logger;
        $callback = function ($msg) use ($exchange, $queue, $ci, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                //调用对应需要处理的逻辑
                $callList = (new \Model\Game3th())->orderCallbackList;
                $arr = json_decode($msg->body, true);
                foreach ($callList as $class => $func) {
                    if (!class_exists($class)) {
                        continue;
                    }
                    if (!is_callable(array($class, $func))) {
                        continue;
                    }
                    $c = new $class($ci);
                    $c->$func($arr);
                    //call_user_func([$class,$func],$arr);  这样调用 构造函数缺少参数
                }
            } catch (\Exception $e) {
                $logger->error("【 $exchange, $queue 】" . $msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    $proccId++;
    // 十分彩订单通知4   只传递结算的订单
    if ($worker->id === $proccId) {
        $exchange = 'synchronousOrderCallback_111';
        $tid = $app->getContainer()->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid;
        $ci = $app->getContainer();
        $logger = $app->getContainer()->logger;
        $callback = function ($msg) use ($exchange, $queue, $ci, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                //调用对应需要处理的逻辑
                $callList = (new \Model\Game3th())->orderCallbackList;
                $arr = json_decode($msg->body, true);
                foreach ($callList as $class => $func) {
                    if (!class_exists($class)) {
                        continue;
                    }
                    if (!is_callable(array($class, $func))) {
                        continue;
                    }
                    $c = new $class($ci);
                    $c->$func($arr);
                    //call_user_func([$class,$func],$arr);  这样调用 构造函数缺少参数
                }
            } catch (\Exception $e) {
                $logger->error("【 $exchange, $queue 】" . $msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    $proccId++;
    // 五分彩订单通知5  只传递结算的订单
    if ($worker->id === $proccId) {
        $exchange = 'synchronousOrderCallback_112';
        $tid = $app->getContainer()->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid;
        $ci = $app->getContainer();
        $logger = $app->getContainer()->logger;
        $callback = function ($msg) use ($exchange, $queue, $ci, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                //调用对应需要处理的逻辑
                $callList = (new \Model\Game3th())->orderCallbackList;
                $arr = json_decode($msg->body, true);
                foreach ($callList as $class => $func) {
                    if (!class_exists($class)) {
                        continue;
                    }
                    if (!is_callable(array($class, $func))) {
                        continue;
                    }
                    $c = new $class($ci);
                    $c->$func($arr);
                    //call_user_func([$class,$func],$arr);  这样调用 构造函数缺少参数
                }
            } catch (\Exception $e) {
                $logger->error("【 $exchange, $queue 】" . $msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    $proccId++;
    // 三分彩订单通知6   只传递结算的订单
    if ($worker->id === $proccId) {
        $exchange = 'synchronousOrderCallback_113';
        $tid = $app->getContainer()->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid;
        $ci = $app->getContainer();
        $logger = $app->getContainer()->logger;
        $callback = function ($msg) use ($exchange, $queue, $ci, $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                //调用对应需要处理的逻辑
                $callList = (new \Model\Game3th())->orderCallbackList;
                $arr = json_decode($msg->body, true);
                foreach ($callList as $class => $func) {
                    if (!class_exists($class)) {
                        continue;
                    }
                    if (!is_callable(array($class, $func))) {
                        continue;
                    }
                    $c = new $class($ci);
                    $c->$func($arr);
                    //call_user_func([$class,$func],$arr);  这样调用 构造函数缺少参数
                }
            } catch (\Exception $e) {
                $logger->error("【 $exchange, $queue 】" . $msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }

    /*$proccId++;
    // 昨天的第三方订单遗漏的部分同步到orders表 7
    if ($worker->id === $proccId) {
        $interval = 60 * 60;
        $logger = $app->getContainer()->logger;
        \Workerman\Lib\Timer::add($interval, function () use ($logger) {
            $logger->info("游戏补单");
            \Logic\GameApi\GameApi::order_repair();
        });
    }*/

    $proccId++;
    // 第三方转入钱失败，退钱
    if ($worker->id === $proccId) {
        $interval = 10;
        $logger = $app->getContainer()->logger;
        \Workerman\Lib\Timer::add($interval, function () use ($logger) {
            $logger->info("第三方游戏退钱");
            \Logic\GameApi\GameApi::gameMoneyErrorRefund();
        });
    }

    $proccId++;
    // 合并打码量
    if ($worker->id === $proccId) {
        $interval = 60;
        $ci     = $app->getContainer();
        $logger = $app->getContainer()->logger;
        \Workerman\Lib\Timer::add($interval, function () use ($logger,$ci) {
            $logger->info("合并打码量");
            (new \Logic\GameApi\Common($ci))->handleDml();
        });
    }

    $proccId++;
    // 更新orders_report
    if ($worker->id === $proccId) {
        $interval = 300;//5分钟一次
        $logger = $app->getContainer()->logger;
        \Workerman\Lib\Timer::add($interval, function () use ($logger,$app) {
            $config = \Logic\Set\SystemConfig::getModuleSystemConfig('rakeBack');
            if(isset($config['bkge_open']) && $config['bkge_open'] === false){
                return;
            }
            //是否开启全民股东返佣 或者盈亏返佣
            if((isset($config['bkge_open_unlimited']) && $config['bkge_open_unlimited'] === true) || (isset($config['bkge_open_loseearn']) && $config['bkge_open_loseearn'] === true)){
                $logger->info("更新orders_report");
                \Logic\GameApi\GameApi::handleOrdersReport();
            }
        });
    }

    $proccId++;
    // 队列 转入第三方金额 STG 10
    if ($worker->id === $proccId) {
        $exchange = 'synchronousUserBalanceRollIn';
        $tid = $app->getContainer()->get('settings')['app']['tid'];
        $queue = $exchange . '_' . $tid;
        $callback = function ($msg) use ($exchange, $queue,  $logger) {
            try {
                $logger->info("【 $exchange, $queue 】" . $msg->body);
                $params = json_decode($msg->body, true);
                //玩家进入该游戏转入金额
                $gameClass = \Logic\GameApi\GameApi::getApi($params['game_type'], $params['uid']);
                print_r($gameClass->rollInThird());
            } catch (\Exception $e) {
                $logger->error($msg->body);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
        \Utils\MQServer::startServer($exchange, $queue, $callback);
    }
};
\Workerman\Worker::runAll();