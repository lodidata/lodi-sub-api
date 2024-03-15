<?php

try {
    $sql = "SELECT `tags`, COUNT(`tags`) as `sum` FROM `user` where `tags` != 0 group by `tags`";
    $list = (array)\DB::connection('slave')->select($sql);

    foreach ($list as $value) {
        \DB::table('label')->where('id', $value->tags)->update(['sum' => $value->sum]);
    }
} catch (Exception $e) {
    print_r($e->getMessage());
}