<?php
require_once __DIR__ . '/../../repo/vendor/autoload.php';
$settings = require_once __DIR__ . '/../../config/settings.php';
$alias = 'lodiGameOrderServer';

\Workerman\Worker::$logFile = LOG_PATH . '/php/gameOrderServer.log';
$worker = new \Workerman\Worker();
$worker->count = 32;
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
    // 拉取超管注单1 AT
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'AT']]);
        });
    }

    $proccId++;
    // 拉取超管注单2 CG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'CG']]);
        });
    }

    $proccId++;
    // 拉取超管注单3 FC
    if ($worker->id === $proccId) {
        $interval = 30;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'FC']]);
        });
    }

    $proccId++;
    // 拉取超管注单校验4 UG
    if ($worker->id === $proccId) {
        $interval = 120;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'UG']]);
        });
    }

    $proccId++;
    // 拉取超管注单5 AVIA
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'AVIA']]);
        });
    }

    $proccId++;
    // 拉取超管注单校验6 CQ9
    if ($worker->id === $proccId) {
        $interval = 120;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'CQ9']]);
        });
    }

    $proccId++;
    // 拉取超管注单校验7 JDB
    if ($worker->id === $proccId) {
        $interval = 120;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'JDB']]);
        });
    }

    $proccId++;
    // 拉取超管注单校验8 JILI
    if ($worker->id === $proccId) {
        $interval = 10;
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'JILI']]);
        });
    }

    $proccId++;
    // 拉取超管注单校验9 KMQM
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'KMQM']]);
        });
    }

    $proccId++;
    // 拉取超管注单10 SEXYBCRT
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'SEXYBCRT']]);
        });
    }

    $proccId++;
    // 拉取超管注单11 BNG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'BNG']]);
        });
    }

    $proccId++;
    // 拉取超管注单12 DS88
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'DS88']]);
        });
    }

    $proccId++;
    // 拉取超管注单13 SA
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'SA']]);
        });
    }

    $proccId++;
    // 拉取超管注单14 PNG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PNG']]);
        });
    }

    $proccId++;
    // 拉取超管注单15 DG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'DG']]);
        });
    }

    $proccId++;
    // 拉取超管注单16 WM
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'WM']]);
        });
    }

    $proccId++;
    // 拉取超管注单17 AWS AE电子
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'AWS']]);
        });
    }

    $proccId++;
    // 拉取超管注单18 IG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'IG']]);
        });
    }

    $proccId++;
    // 拉取超管注单19 YGG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'YGG']]);
        });
    }

    /* $proccId++;
     // 拉取超管注单20 XG
     if ($worker->id === $proccId) {
         $interval = 120;//2分钟一次
         \Workerman\Lib\Timer::add($interval, function () {
             \Logic\GameApi\GameApi::synchronousData2([['type' => 'XG']]);
         });
     }*/

    $proccId++;
    // 拉取超管注单20 PG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PG']]);
        });
    }

    $proccId++;
    // 拉取超管注单21 PB
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'PB']]);
        });
    }

    $proccId++;
    // 拉取超管注单22 MG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'MG']]);
        });
    }

    $proccId++;
    // 拉取超管注单23 STG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'STG']]);
        });
    }

    $proccId++;
    // 拉取超管注单24 GFG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'GFG']]);
        });
    }

    $proccId++;
    // 拉取超管注单25 EVO
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'EVO']]);
        });
    }

    $proccId++;
    // 拉取超管注单26 NG
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'NG']]);
        });
    }

    $proccId++;
    // 拉取超管注单27 YESBINGO
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'YESBINGO']]);
        });
    }

    $proccId++;
    // 拉取超管注单28 EVORT
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'EVORT']]);
        });
    }

    $proccId++;
    // 拉取超管注单29 WMATCH
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'WMATCH']]);
        });
    }

    $proccId++;
    // 拉取超管注单30 EVOPLAY
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'EVOPLAY']]);
        });
    }

    $proccId++;
    // 拉取超管注单31 FACHAI
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'FACHAI']]);
        });
    }

    $proccId++;
    // 拉取超管注单32 DS88DJ
    if ($worker->id === $proccId) {
        $interval = 120;//2分钟一次
        \Workerman\Lib\Timer::add($interval, function () {
            \Logic\GameApi\GameApi::synchronousData2([['type' => 'DS88DJ']]);
        });
    }
};
\Workerman\Worker::runAll();