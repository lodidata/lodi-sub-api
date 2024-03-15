<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/10
 * Time: 14:46
 */

namespace Logic\Admin;

use Model\Admin\Active as ActiveModel;
use Model\Admin\ActiveRule as ActiveRuleModel;
use Illuminate\Database\Capsule\Manager as DB;

class Active extends \Logic\Logic
{

    protected $activeModel;
    protected $activeRuleModel;


    public function __construct($ci)
    {
        parent::__construct($ci);

        $this->activeModel = new ActiveModel();
        $this->activeRuleModel = new ActiveRuleModel();
    }

    /**
     * 公告列表
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function getNewActiveList($params, $page = 1, $size = 10)
    {

//        if ($size > 20) $size = 20;


//        $query = ActiveModel::from('active as a')
        $query = DB::connection('slave')->table('active as a')
            ->leftJoin('active_rule as r','a.id', '=','r.active_id')
            ->leftJoin('active_template as at','a.type_id', '=','at.id')
            ->leftJoin('active_type as aty','a.active_type_id', '=','aty.id')
//            ->leftJoin('active_handsel as h','a.id', '=','h.active_id')
            ->select([
                'a.id', 'a.title', 'a.vender_type', 'a.updated', 'a.content_type', 'a.content',
                'a.description', 'a.cover', 'a.begin_time', 'a.state',
                'a.end_time', 'a.sort', 'a.status', 'a.created_user',
                'a.created_user as updated_user', 'a.created', 'a.active_type_id', 'aty.name as type_name', 'at.name as template_name',
                'r.rule', 'a.type_id as template_id', 'r.withdraw_require_val',
                'r.issue_mode', 'r.send_type', 'r.send_max', 'r.send_bet_max', 'r.bind_info', 'r.give_time', 'r.luckydraw_condition', 'r.limit_times', 'r.member_level','a.blacklist_url',
                'r.issue_time', 'r.issue_day', 'r.issue_cycle', 'r.give_condition', 'r.give_date', 'r.game_type', 'r.limit_value', 'a.send_times', 'a.apply_times', 'a.condition_recharge', 'a.condition_user_level', 'a.link'
//                'h.msg_title','h.msg_content','h.give_away','h.phone_list','h.user_level','h.batch','h.batch_url','h.give_amount','h.dm_num','h.notice_type','h.give_amount_time','h.is_now_give'
            ]);
        $query->where('type_id', '!=', 12);
        if (isset($params['id'])) {
            $query = $query->where('a.id', $params['id']);
        }
        if (isset($params['language_id'])) {
            $query = $query->where('a.language_id', $params['language_id']);
        }
//        if(isset($params['template_id']) && $params['template_id'] != 4){
        if (isset($params['template_id'])) {
            $query = $query->where('a.active_type_id', $params['template_id']);
        }
        if (isset($params['title'])) {
            $query = $query->where('a.title', "{$params['title']}");
        }

        $query = isset($params['status']) && !empty($params['status']) ? $query->where('a.status', $params['status']) : $query;

        $attributes['total'] = $query->count();
        $attributes['number'] = $page;
        $res = $query->orderBy('sort')->orderBy('a.id', 'DESC')->forPage($page, $size)->get()->toArray();
        if (!$res) {
            return [];
        }

        $sysRebetConfig = \Logic\Set\SystemConfig::getModuleSystemConfig('rebet_config');
        foreach($res as $k=> &$v){
//            if(!$v['template_id']){
//                $v['template_id'] = 4;
//            }
            if(!$v->template_id){
                $v->template_id = 4;
            }
            $v->cover = showImageUrl($v->cover);
            $v->blacklist_url = showImageUrl($v->blacklist_url);
            $v->description = $v->description ? mergeImageUrl($v->description) : '';
            if($v->template_id == 8) {
                $v->rebet_multiple = $sysRebetConfig['week'];
                $v->rebet_gt_zero_switch = $sysRebetConfig['week_gt_zero'];
            }
            if($v->template_id == 9) {
                $v->rebet_multiple = $sysRebetConfig['month'];
                $v->rebet_gt_zero_switch = $sysRebetConfig['month_gt_zero'];
            }
            if(!empty($v->rule) && is_json($v->rule)){
                $rules = json_decode($v->rule, true);
                if(!is_array($rules)){
                    continue;
                }
                foreach($rules as &$val){
                    if(isset($val['img'])){
                        $val['img'] = showImageUrl($val['img']);
                    }
                }
                $v->rule = json_encode($rules);
                unset($val);
            }
        }

        return $this->lang->set(0, [], $res, $attributes);
    }


}