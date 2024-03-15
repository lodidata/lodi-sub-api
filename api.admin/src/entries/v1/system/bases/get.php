<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '获取系统设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['system' =>'', 'register'=>'','registerCon'=>'','rakeBack'=>'','new_market'=>'','market'=>'','market2'=>'','activity'=>'','login'=>'','audit'=>'','agent'=>'','user_agent'=>'','admin_agent'=>'','admin_pin_password'=>'','domain'=>'','network_safty'=>''];
    public function run() {
        $desc = ['rakeBack'=>'返佣','audit'=>'稽核'];
        $res = array_intersect_key(\Logic\Set\SystemConfig::getGlobalSystemConfig(),$this->module);
        $game = \Model\Admin\GameMenu::where('pid',0)->where('switch','enabled')->get(['type','name'])->toArray();
        $game = array_column($game,null,'type');
        foreach ($desc as $key=>$val) {
            foreach ($res[$key] as $k=>$v) {
                switch ($k){
                    case 'agent_switch':
                        $name = 'National agent open/close';
                        break;
                    case 'bkge1':
                        $name = '2nd level commission';
                        break;
                    case 'bkge2':
                        $name = '1st level commission';
                        break;
                    case 'bkge_open':
                        $name = 'Whether to enable commission';
                        break;
                    case 'bkge_open_third':
                        $name = 'Whether to enable the 3-level commission';
                        break;
                    case 'bkge_open_unlimited':
                        $name = 'Whether to open the national shareholder commission';
                        break;
                    case 'bkge_open_loseearn':
                        $name = 'Whether to enable profit and loss settlement commission';
                        break;
                    case 'bkge_settle_type':
                        $name = 'Profit and loss settlement method';
                        break;
                    case 'bkge_calculation_self':
                        $name = '返佣结算是否算本身';
                        break;
                    default:
                        $name =  isset($game[$k]) ? $game[$k]['name'] : '';
                }
                $tmp = [
                    'key' => $k,
                    'value' => $v,
                    'name' => $this->lang->text($name),
                ];
                if($k=='agent_switch'){
                    $res['rakeBack1'][] = $tmp;
                }elseif(in_array($k, ['bkge_calculation_self','bkge_open','bkge_open_third','bkge_open_unlimited','bkge_open_loseearn','bkge_settle_type'])){
                    $res['rakeBack2'][] = $tmp;
                }else{
                    $res[$key][] = $tmp;
                }
                unset($res[$key][$k]);

            }
        }
        $res['registerCon'] = array_values($res['registerCon']);

        //短信类型列表
        $smsList = $this->ci->get('settings')['website']['captcha']['dsn'];
        $res['system']['smsList'] = array_keys($smsList);

        //客服VIP处理
        $res['system'] = $this->deal_kefu_vip($res['system']);

        return $res;
    }

    //客服VIP处理
    public function deal_kefu_vip($data){
        //获取所有vip
        $user_level = DB::table('user_level')->select(['id','name'])->orderBy('id','asc')->get()->toArray();
        $kefu_vip_list[] = [
            'id' => 0,
            'name' => '未登录',
        ];
        foreach($user_level as $key => $val){
            $kefu_vip_list[] = [
                'id' => $val->id,
                'name' => $val->name,
            ];
        }
        $data['kefu_vip_list'] = $kefu_vip_list;

        return $data;
    }
};
