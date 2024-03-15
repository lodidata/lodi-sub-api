<?php

namespace Model;

class UserLogs extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'user_logs';

    public $timestamps = false;
    //UserLog模型用来写入了  这个用来查询
    protected $connection = 'slave';
    public function getLogTypeAttribute($value) {
        global $app;
        $ci = $app->getContainer();
        $arr = [
            $ci->lang->text('Other'),
            $ci->lang->text('Login'),
            $ci->lang->text('Withdrawal application'),
            $ci->lang->text('Recharge application'),
            $ci->lang->text('apply for Activity Award'),
            $ci->lang->text('modify login password'),
            $ci->lang->text('change the withdrawal password'),
            $ci->lang->text('modify personal information'),
            $ci->lang->text('member registration'),
            $ci->lang->text('agent registration'),
            $ci->lang->text('transfer'),
            $ci->lang->text('modify bank card information'),
            $ci->lang->text('betting'),
            $ci->lang->text('Other'),
        ];

        return $arr[$value];
    }
}

