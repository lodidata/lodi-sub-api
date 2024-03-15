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
       /* $notice = new \Logic\User\Notice($this->ci);
        $params = $this->request->getParams();
        $sql = DB::table('super_notice')->orderBy('id','desc');
        $sql = $sql->where('status', 1);//查询发送对象
        $sql = isset($params['menu_id']) ? $sql->where('menu_id', $params['menu_id']) : $sql;
        $sql = isset($params['game_id']) ? $sql->where('game_id', $params['game_id']) : $sql;
        $sql = isset($params['s_time']) ? $sql->where('pub_time', '>=', $params['s_time']) : $sql;
        $sql = isset($params['e_time']) ? $sql->where('pub_time', '<=', $params['e_time']) : $sql;
        $total = $sql->count();
        $data = $sql->orderBy('id', 'desc')->forPage($params['page'], $params['page_size'])->get()->toArray();
        $menu = ['0'=>'其他'];
        $game = ['0'=>''];
        foreach($data as $key=>&$val){

            //获取一级菜单
            if(isset($menu[$val->menu_id])){
                $val->menu_name = $menu[$val->menu_id];
            }else{
                $c = DB::table('game_menu')->where('id', $val->menu_id)->first(['name']);
                $val->menu_name = $menu[$val->menu_id] = $c->name;
            }
            //获取二级菜单
            if(isset($game[$val->game_id])){
                $val->game_name = $game[$val->game_id];
            }else{
                $c = DB::table('game_menu')->where('id', $val->game_id)->first(['name']);
                $val->game_name = $game[$val->game_id] = $c->name;
            }
        }
        return $this->lang->set(0, [], $data, ['number' => $params['page'], 'size' => $params['page_size'], 'total' => $total]);*/
    }
};