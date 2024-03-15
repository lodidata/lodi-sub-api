<?php
/**
 * 用户层级模型
 */
namespace Model;

class UserLevel extends \Illuminate\Database\Eloquent\Model{
    protected $table = 'user_level';
    public $timestamps = false;
    protected $fillable = [
      'id',
      'name',
      'level',
      'icon',
      'lottery_money',
      'deposit_money',
      'user_count',
      'offline_dml_percent',
      'online_dml_percent',
      'upgrade_dml_percent',
      'promote_handsel',
      'promote_ways',
      'transfer_handsel',
      'transfer_ways',
      'draw_count',
      'comment',
    ];
}



