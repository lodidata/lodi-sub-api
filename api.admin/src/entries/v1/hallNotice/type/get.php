<?php
/**
 * 厅主的公告列表
 * @author Taylor 2019-01-21
 */
use Logic\Admin\BaseController;

return new class extends BaseController
{
    const TITLE = 'GET 公告列表';
    const PARAMS = [
        'menu_id' => 'integer()#一级菜单id，0表示其他',
        'game_id' => 'integer()#二级菜单，游戏id，0表示没有二级菜单',
        's_time' => 'datetime()#发布时间的开始时间',
        'e_time' => 'datetime()#发布时间的结束时间',
        'page' => 'int()#页码',
        'page_size' => 'int()#每页大小',
    ];
    const SCHEMAS = [
        [
            'id' => 'integer#消息id',
            'admin_uid' => 'integer#管理员id',
            'admin_name' => 'integer#管理员用户名',
            'customer_id' => 'integer#发送对象即客户ID，0表示全部',
            'menu_id' => 'integer#一级菜单id，0表示其他',
            'game_id' => 'integer#二级菜单，游戏id，0表示没有二级菜单',
            'title' => 'string#标题',
            'content' => 'string#内容',
            'start_time' => 'string#开始时间',
            'end_time' => 'string#结束时间',
            'pub_time' => 'string#发布时间',
            'status' => 'int#状态（1：发布，0：未发布）',
            'created' => 'string#创建时间',
            'updated' => 'string#更新时间',
        ]
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        return $this->lang->set(0);
        /*//一级菜单
        $menu = DB::table('game_menu')->where('pid', 0)->get(['id', 'type', 'name'])->toArray();
        if(!empty($menu)){
            foreach($menu as &$m){
                $m->second = '';
                if($m->type != 'CP'){//彩票没有二级菜单
                    $s_menu = DB::table('game_menu')->where('pid', $m->id)->get(['id', 'type', 'name'])->toArray();
                    if(empty($s_menu)){
                        $m->second = '';
                    }else{
                        $m->second = $s_menu;
                    }
                }
            }
        }
        array_push($menu, ['id'=>0, 'menu'=>'其他', 'second'=> '']);
        return $this->lang->set(0, [], $menu);*/
    }
};