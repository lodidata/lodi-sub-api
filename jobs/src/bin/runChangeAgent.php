<?php
$agent_list=[
    '11000'=>[
        'sex101','smsrenodp','shoTW','shotk','shoyt','shoem','shoma','shols','Gilbert888','meta001','meta002','meta003','meta004','meta005','meta006','meta007','meta008','meta009','meta010'
    ],
    '10001'=>['jing01'],
    '10002'=>['jing02'],
    '10003'=>['jing03'],
    '10004'=>['jing04'],
    '10005'=>['jing05'],
    '10006'=>['jing06'],
    '10007'=>['jing07'],
    '10008'=>['jing08'],
    '10009'=>['jing09'],
    '10010'=>['jing10'],
    '10011'=>['jing11'],
    '10012'=>['jing12'],
    '10013'=>['jing13'],
    '10014'=>['jing14'],
    '10015'=>['jing15'],
    '10016'=>['jing16'],
    '10017'=>['jing17'],
    '10018'=>['jing18'],
    '10019'=>['jing19'],
    '10020'=>['jing20'],
];

$ci = $app->getContainer();
$agent=new \Logic\User\Agent($ci);
$agent->batchAgent($agent_list);

die('执行完毕');

