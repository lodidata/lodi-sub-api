
insert into system_config(`module`,`name`,`type`,`key`,`value`,`desc`,`state`) values('user_agent','自身流水','bool','bet_amount','1','1开，0关','enabled'),
                                                                                     ('user_agent','下级流水','bool','next_bet_amount','1','1开，0关','enabled'),
                                                                                     ('user_agent','总流水','bool','total_bet_amount','1','1开，0关','enabled'),
                                                                                     ('user_agent','注册用户','bool','new_register','1','1开，0关','enabled'),
                                                                                     ('user_agent','下级人数','bool','next_agent','1','1开，0关','enabled'),
                                                                                     ('user_agent','总充值人数','bool','recharge_user','1','1开，0关','enabled'),
                                                                                     ('user_agent','总充值金额','bool','recharge_amount','1','1开，0关','enabled'),
                                                                                     ('user_agent','盈亏金额','bool','profits','1','1开，0关','enabled'),
                                                                                     ('admin_agent','自身流水','bool','bet_amount','1','1开，0关','enabled'),
                                                                                     ('admin_agent','下级流水','bool','next_bet_amount','1','1开，0关','enabled'),
                                                                                     ('admin_agent','总流水','bool','total_bet_amount','1','1开，0关','enabled'),
                                                                                     ('admin_agent','股东分红','bool','profits','1','1开，0关','enabled'),
                                                                                     ('admin_agent','股份','bool','proportion','1','1开，0关','enabled'),
                                                                                     ('admin_agent','公司成本','bool','fee_amount','1','1开，0关','enabled'),
                                                                                     ('admin_agent','新注册用户','bool','new_register','1','1开，0关','enabled'),
                                                                                     ('admin_agent','首充人数','bool','first_recharge_user','1','1开，0关','enabled'),
                                                                                     ('admin_agent','首存金额','bool','first_recharge_amount','1','1开，0关','enabled'),
                                                                                     ('admin_agent','总充值金额','bool','all_recharge_amount','1','1开，0关','enabled'),
                                                                                     ('admin_agent','有效用户','bool','valid_user','1','1开，0关','enabled'),
                                                                                     ('admin_agent','有效投注','bool','valid_amount','1','1开，0关','enabled'),
                                                                                     ('admin_agent','投注人数','bool','bet_user','1','1开，0关','enabled');