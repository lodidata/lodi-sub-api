<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Utils\www\Controller;
// DIC configuration
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$container = $app->getContainer();

// view renderer
// $container['renderer'] = function ($c) {
//     $settings = $c->get('settings')['renderer'];
//     return new Slim\Views\PhpRenderer($settings['template_path']);
// };

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    if (isset($settings['type']) && $settings['type'] == 'file') {
//        $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'], 0, $settings['level']));//每天生成一个日志
    }


    return $logger;
};


$container['db'] = function ($c) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    foreach ($c['settings']['db'] as $key => $v) {
        $capsule->addConnection($v, $key);
    }
    $capsule->setEventDispatcher(new Dispatcher(new Container));
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};

$container['cache'] = function ($c) {
    $settings   = $c->get('settings')['cache'];
    $config     = [
        'scheme' => $settings['scheme'],
        'host' => $settings['host'],
        'port' => $settings['port'],
        'database' => $settings['database'],
    ];

    if (!empty($settings['password'])) {
        $config['password'] = $settings['password'];
    }
    if($config['scheme'] == 'tls'){
        $config['ssl'] = $settings['ssl'];
    }

    return new Predis\Client($config);
};

$container['redis'] = $container['cache'];

$container['redisCommon'] = $container['cache'];


$container['lang'] = function ($c) {
    return new \Logic\Define\Lang($c);
};

$site_type = $website = $container->get('settings')['website']['site_type'];
if($site_type == 'ncg'){
    define('LANG', 'th');
}elseif($site_type == 'es-mx'){
    define('LANG', 'es-mx');
}else{
    define('LANG', 'en-us');
}

/*$container['validator'] = function ()  {
    return new Awurth\SlimValidation\Validator(true, require_once __DIR__ . '/../../config/lang/'.LANG.'/validator.php');
};*/
$container['notFoundHandler'] = function ($c) {
    return function ($req, $res) use ($c) {
        return $res->write('');
    };
};