insert into pay_config(`name`,`type`,`partner_id`,`key`,`pub_key`,`payurl`,`ip`,`show_type`,`status`,`sort`,`return_type`,`link_data`,`pay_type`,`params`) values('JHPAY','jhpay','','','','http://www.phpay158.com','','h5','disabled',16,'json','','','{}');

INSERT INTO `transfer_config`( `name`, `balance`, `code`, `ver`, `app_id`, `app_secret`, `sort`, `status`, `key`, `pub_key`, `token`, `terminal`, `partner_id`, `url_notify`, `url_return`, `max_money`, `min_money`, `fee`, `email`, `request_code`, `bank_list`, `request_url`, `pay_callback_domain`, `params`) VALUES ('JHPAY', 0, 'JHPAY', NULL, '0', NULL, 1, 'disabled', '', '', NULL, NULL, '', NULL, NULL, 0, 0, 500, NULL, NULL, '{\"Gcash\":\"Gcash\"}', 'http://www.phpay158.com', '', 'null');



#++++++++++++++++++++++bet77

update pay_config set partner_id ='10006',`key` ='22bcgkkdcb9itiqm712vf4nr92ww0sbl',pub_key ='22bcgkkdcb9itiqm712vf4nr92ww0sbl',`status`='enabled' where `type`='jhpay';

UPDATE transfer_config SET partner_id ='10006',`key` ='22bcgkkdcb9itiqm712vf4nr92ww0sbl',pub_key ='22bcgkkdcb9itiqm712vf4nr92ww0sbl',`status`='enabled' WHERE `name`='JHPAY';



#redis bet77

del pay_config_list
