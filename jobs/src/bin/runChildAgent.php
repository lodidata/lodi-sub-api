<?php
global $app;
$logger = $app->getContainer()->logger;

$startTime = time();
$childs_agents = [];
//$insertChildAgents = [];
//所有上下级关系
$childs_agents = \DB::table('child_agent')->selectRaw("concat(pid,'-',cid) as pcid")->pluck('pcid')->toArray();
    //清空所有关系
 //   \DB::table('child_agent')->truncate();

    //补child_agent 所有上下级关系
    $agentList = (array)\DB::table('user_agent')->where('uid_agent', '>', 0)->get(['id','uid_agent', 'uid_agents','level'])->toArray();;
    foreach ($agentList as $agent) {
        $agent = (array)$agent;
        $ids = explode(',', $agent['uid_agents']);
        $count = count($ids);
        if($count==1 || $count !=$agent['level']){
            \DB::table('user_agent')->where('id', $agent['id'])->update(['level' => $count]);
            $logger->error('userAgent level error', ['id' => $agent['id'], 'level' => $agent['level'], 'newlevel' => $count]);
            continue;
        }
        if(!in_array($agent['uid_agent'], $ids)){
            $logger->error('userAgent uid_agents error', $agent);
            continue;
        }

        for($i=0;$i<$count;$i++){
            for($j=$i+1; $j<$count; $j++){
                if($i==$j){
                    continue;
                }
                $where = [
                    'pid' => $ids[$i],
                    'cid' => $ids[$j]
                ];
               $pcid = $ids[$i].'-'.$ids[$j];
                if(!in_array($pcid, $childs_agents)){
                    \DB::table('child_agent')->insert($where);
                    $logger->error('childAgent error', $where);
                    $childs_agents[] = $pcid;
                   // $insertChildAgents[] = $where;
                    unset($pcid, $where);
                }
            }
        }
        unset($i,$j,$count,$agent);
    }
    unset($agentList,$childs_agents);

/*
    //批量插入
    $page = 1;
    $pageSize = 100;
    $total_count = count($insertChildAgents);
    $page_count = ceil($total_count / 100);
    while (1) {
        if ($page > $page_count) {
            break;
        }
        $page_start = ($page - 1) * $pageSize;
        $subData = array_slice($insertChildAgents, $page_start, $pageSize);
        $subDataCount = count($subData);
        try {
            \DB::table('child_agent')->insert($subData);
        } catch (\Exception $e) {

        }
       $page++;
    }*/
$endTime = time();
echo 'finish time :' . ($endTime-$startTime);
