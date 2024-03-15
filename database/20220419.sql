

INSERT INTO `pay_config` (`id`, `name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `ip`, `status`) VALUE(4,'DINGPEI','dingpei','20434735','92B8EB7D63F1113391D6AB25E10BAA8A','92B8EB7D63F1113391D6AB25E10BAA8A', 'http://13.72.210.52:8080', '13.72.210.52', 'enabled');



INSERT INTO `transfer_config` (`id`, `name`, `code`, `status`, `key`, `pub_key`,`partner_id`,`fee`, `bank_list`, `request_url`) VALUE(4,'DINGPEI','DINGPEI','enabled','92B8EB7D63F1113391D6AB25E10BAA8A','92B8EB7D63F1113391D6AB25E10BAA8A', '20434735',0, '{"Gcash":"Gcash"}', 'http://13.72.210.52:8080');