<?php
 $ids = \Model\User::pluck('id')->toArray();
foreach ($ids as $id) {
    //echo $id,PHP_EOL;
    DB::table('user_agent')->where('user_id',$id)->update([
        'inferisors_num' =>  DB::table('user_agent')->where('uid_agent',$id)->count(),
        'inferisors_all' =>  DB::table('child_agent')->where('pid',$id)->count()+1 //自己也是团队的一团
    ]);
}
echo 'finish';
