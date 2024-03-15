<?php
/**
 * User: nk
 * Date: 2019-01-07
 * Time: 09:02
 * Des : 日志管理系统
 */

namespace Model\Admin;

use Illuminate\Database\Eloquent\Model;
use Logic\Admin\BaseController;
use Utils\Client;
use Utils\Utils;

class LogicModel extends Model
{

    public $logs_type;
    public $opt_desc = '';
    public $desc_desc;//备注信息（需拼接上的信息）
    // 操作目标 user_id
    private $target_uid = null;
    // 操作目标昵称 user_id
    private $target_uname = null;

    /**
     * 记录日志
     * @param string $type 操作类型  (新增 ，修改，删除)
     * @param bool $status
     * @param array $logic 日志详情
     */
    public function log()
    {
        global $app;
        $method = $app->getContainer()->request->getMethod();
        $dir = BaseController::getRequestDir();
        $action = \DB::table('admin_user_role_auth')->where('method', $method)
            ->where('path', $dir)->first();
        if (!$action) return;
        $module_child = \DB::table('admin_user_role_auth')->where('id', $action->pid)->first();
        $module = \DB::table('admin_user_role_auth')->where('id', $module_child->pid)->value('name');
        global $playLoad;
        $data = [
            'ip' => Client::getIp(),
            'uid' => $playLoad['uid'] ?? 0,
            'uname' => $playLoad['nick'] ?? '',
            'uid2' => $this->target_uid,
            'uname2' => $this->target_uname,
            'module' => $module,
            'module_child' => $module_child->name,
            'fun_name' => $action->name, //这个暂时一样
            'type' => $this->logs_type,
            'status' => 1,
            'remark' => $this->opt_desc
        ];

        // 改一次后,对象直接失效
        if ($this->target_uid != null || $this->target_uname) {
            $this->target_uid = null;
            $this->target_uname = null;
        }

        \DB::table('admin_logs')->insert($data);
    }


    public static function boot()
    {
        parent::boot();
        static::created(function ($obj) {
            $obj->logs_type = '新增';
            $opt_desc = $obj->attributes[$obj->title_param];
            if (isset($obj->title_decrypt) && $obj->title_decrypt) {
                $opt_desc = Utils::RSADecrypt($opt_desc);
            }
            $obj->opt_desc = $obj->title_name . '(' . $opt_desc . ')';
            $obj->log();
        });
        static::updated(function ($obj) {

//          $obj->attributes;  更新后的值  $obj->original; 更新前的值
            $charge = array_diff_assoc($obj->attributes, $obj->original);
//            $obj->type = '修改/更新';
            $obj->logs_type = $obj->logs_type != '' || $obj->logs_type != null ? $obj->logs_type : '修改/更新';
            $opts = $obj->desc_attributes;
            foreach ($charge as $key => $val) {
                if (!isset($opts[$key]) || !is_array($opts[$key])) {
                    continue;
                }
                $desc = $opts[$key];
                if (is_array($desc['key'])) {
                    if ($desc['multi']) {
                        $tmp1 = $val ? explode(',', $val) : [];
                        $tmp2 = $obj->original[$key] ? explode(',', $obj->original[$key]) : [];
                        $str = '';
                        foreach ($desc['key'] as $tk => $tv) {
                            if (in_array($tk, $tmp1) && !in_array($tk, $tmp2)) {
                                $str = $tv[0] . ',';
                            } elseif (!in_array($tk, $tmp1) && in_array($tk, $tmp2)) {
                                $str = $tv[1] . ',';
                            }
                        }
                        if (!$str) continue;
                        $obj->opt_desc = rtrim($str, ',');
                    } else {
                        $obj->opt_desc = $desc['key'][$val];
                    }
                } else {
                    $obj->opt_desc = $desc['key'];
                }
                //带上更改后的值
                if ($desc['value']) {
                    $before_val = isset($desc['decrypt']) && $desc['decrypt'] ? Utils::RSADecrypt($obj->original[$key]) : $obj->original[$key];
                    $val = isset($desc['decrypt']) && $desc['decrypt'] ? Utils::RSADecrypt($val) : $val;
                    if (isset($desc['table']) && $desc['table']) {
                        $val = \DB::table($desc['table'])->where($desc['t_id'], $val)->value($desc['t_val']);
                        $before_val = \DB::table($desc['table'])->where($desc['t_id'], $before_val)->value($desc['t_val']);
                    }
                    if(isset($desc['unit']) && $desc['unit']){
                        $obj->opt_desc .= '(' . $before_val/100 . ' 改 ' . $val/100 . ')';
                    }else{
                        $obj->opt_desc .= '(' . $before_val . ' 改 ' . $val . ')';
                    }

                }
                $obj->opt_desc = $obj->desc_desc . $obj->opt_desc;
                $obj->log();
            }
        });
        static::deleted(function ($obj) {
            $obj->logs_type = '删除';
            $opt_desc = $obj->attributes[$obj->title_param];
            if (isset($obj->title_decrypt) && $obj->title_decrypt) {
                $opt_desc = Utils::RSADecrypt($opt_desc);
            }
            $obj->opt_desc = $obj->title_name . '(' . $opt_desc . ')';
            $obj->log();
        });
    }

    /**
     * 初始化数据库字段注释
     */
    private function initFieldsComment()
    {
        $tb_list = \DB::select('SHOW FULL COLUMNS FROM ' . $this->getTable());
        foreach ($tb_list as $item) {
            if (isset($item->Comment) && strlen($item->Comment) > 0) {
                $this->fields_comment[$item->Field] = $item->Comment;
            } else {
                $this->fields_comment[$item->Field] = $item->Field;
            }
        }
    }

    /**
     * 设置操作目标对象 (一次性)
     * @param int $uid
     * @param string $uname
     */
    public function setTarget(int $uid, string $uname)
    {
        $this->target_uid = $uid;
        $this->target_uname = $uname;
    }

    /**
     * 保存
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        if ($options) {
            $this->attributes = $options;   //更新时有，新增该参数为空，则为赋值
        }
        // 保存
        $state = parent::save($options);
        return $state;
    }

}