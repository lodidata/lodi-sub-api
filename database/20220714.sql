#菲版
alter table pay_config add `params` json NOT NULL COMMENT '支付差异参数';
update pay_config set `params` = '{"countryCode":"PHL","currencyCode":"PHP","paymentType":"902410172001"}' where `type` = 'bpay' limit 1;
alter table transfer_config add `params` json NOT NULL COMMENT '支付差异参数';
update transfer_config set `params` = '{"countryCode":"PHL","currencyCode":"PHP","transferType":"902410175001"}' where `code` = 'BPAY' limit 1;
update transfer_config set `params` = '{"checkCard":1}' where `code` = 'POPPAY' or `code` = 'TOPPAY';

#泰版
alter table pay_config add `params` json NOT NULL COMMENT '支付差异参数';
update pay_config set `params` = '{"countryCode":"THA","currencyCode":"THB","paymentType":"903210232002"}' where `type` = 'bpay' limit 1;
alter table transfer_config add `params` json NOT NULL COMMENT '支付差异参数';
update transfer_config set `params` = '{"countryCode":"THA","currencyCode":"THB","transferType":"903210235002"}' where `code` = 'BPAY' limit 1;
update transfer_config set `params` = '{"checkCard":1}' where `code` = 'AUTOTOPUP';