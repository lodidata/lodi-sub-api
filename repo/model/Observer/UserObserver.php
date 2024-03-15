<?php
namespace Model\Observer;

use \Model\User;

class UserObserver {

    public function creating(User $model) {
        $ip =  \Utils\Client::getIp();
        $model->ip = \DB::raw("inet6_aton('$ip')");
        $model->salt = User::getGenerateChar(6);
        $model->password = md5(md5($model->password) . $model->salt);
    }

    public function updating(User $model) {
        $model->salt = User::getGenerateChar(6);
        $model->password = md5(md5($model->password) . $model->salt);
    }

    public function saving(User $model) {
        $model->salt = User::getGenerateChar(6);
        $model->password = md5(md5($model->password) . $model->salt);
    }
}