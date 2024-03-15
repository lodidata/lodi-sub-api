<?php

namespace Logic\User;

/**
 * 用户模块
 */
class Auth extends \Logic\Logic {

    protected $dir;
    protected $method;
    protected $searchUser = [
            'user-list' => 'name',
            'lottery-play-records' => 'user_name',
            'lottery-play-chase' => 'user_name',
            'funds-flow' => 'username',
            'cash-record-transfer' => 'username',
            'report-agent' => 'user_name',
            'report-user' => 'user_name',
            'cash-profit.loss' => 'user_name',
            'active-applys' => 'user_name',
            'pc28-rebet' => 'user_name',
            'cash-deposit-offlines' => 'user_name',
            'cash-deposit-onlines' => 'user_name',
            'cash-newwithdraw' => 'member_name',
            'cash-manual.records' => 'username',
            'system-log-user.operation' => 'username',
            'admin-log' => 'uname2',
            'user-3th' => 'uname',
            'servicemanage-session-list' => 'client_third_party_id',
        ];

    protected function initDir() {
        $dir = ltrim($this->request->getUri()->getPath(),'/');
        $this->dir = str_replace('/','-',$dir);
        $this->method = strtolower($this->request->getMethod());
    }

    public function allowSearchUser() {
        $this->initDir();
        $kes = array_keys($this->searchUser);
        if( $this->method !== 'get' || !in_array($this->dir,$kes)) {
            return true;
        }
        $name = $this->request->getParam($this->searchUser[$this->dir]);
        if($name) {
            return true;
        }
        global $playLoad;
        $member_control = \DB::table('admin_user_role')
            ->where('id','=',$playLoad['rid'])->value('member_control');
        $member_control = json_decode($member_control,true);
        if(!isset($member_control['user_search_switch']) || $member_control['user_search_switch'] === false) {
            return true;
        }
        return $this->response
            ->withStatus(200)
            ->withJson([
                'data' => null,
                'attributes' => null,
                'state' => 0,
                'message' => $this->lang->text("Please enter the full user name for query"),
            ]);
    }
}