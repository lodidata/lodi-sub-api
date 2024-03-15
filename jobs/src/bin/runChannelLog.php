<?php
$visit = \DB::connection('slave')->table('user_channel_logs')->selectRaw('IFNULL(channel_id,"default") as channel_id,count(log_ip) as click_num')
    ->groupBy(['channel_id'])->get()->toArray();
foreach ($visit as $value) {
    $app->getContainer()->redis->incrBy("channel_total:{$value->channel_id}", $value->click_num);
}

$count = $data = \DB::connection('slave')->table('user_channel_logs')->count();
for ($i = 0; $i < $count; $i += 10000) {
    $data = \DB::connection('slave')->table('user_channel_logs')->selectRaw('channel_id,log_ip')->whereBetween('id', [$i, $i + 10000])->get()->toArray();
    foreach ($data as $value) {
        $app->getContainer()->redis->pfadd("channel_distinct:{$value->channel_id}", $value->log_ip);
    }
}