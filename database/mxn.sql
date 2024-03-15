#MPAY支付与代付
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('MPAY', 'mpay', 'SH75287406', '01eb2cfd21c0b15014e0c1289353d43d', '01eb2cfd21c0b15014e0c1289353d43d', 'https://api.mpayonline.shop', 0, 0, '54.210.243.31', 'h5', '2022-10-26 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','[]');

INSERT INTO level_online (level_id, pay_plat) VALUES(1,'mpay'),(2,'mpay'),(3,'mpay'),(4,'mpay'),(5,'mpay'),(6,'mpay'),(7,'mpay'),(8,'mpay');


INSERT INTO `transfer_config` (`name`,`balance`,`code`,`ver`,`app_id`,`app_secret`,`sort`,`status`,`key`,`pub_key`,`token`,`terminal`,`partner_id`,`url_notify`,`url_return`,`max_money`,`min_money`,`fee`,`email`,`request_code`,`bank_list`,`request_url`,`params`)VALUES('MPAY',0,'MPAY',NULL,'0',NULL,11,'default','01eb2cfd21c0b15014e0c1289353d43d','01eb2cfd21c0b15014e0c1289353d43d',NULL,NULL,'SH75287406',NULL,NULL,0,0,0,NULL,NULL,'{\"ABC CAPITAL\":\"ABC CAPITAL\",\"ACTINVER\":\"ACTINVER\",\"AFIRME\":\"AFIRME\",\"AKALA\":\"AKALA\",\"ARCUS\":\"ARCUS\",\"ASP INTEGRA OPC\":\"ASP INTEGRA OPC\",\"AUTOFIN\":\"AUTOFIN\",\"AZTECA\":\"AZTECA\",\"BaBien\":\"BaBien\",\"BAJIO\":\"BAJIO\",\"BANAMEX\":\"BANAMEX\",\"BANCO FINTERRA\":\"BANCO FINTERRA\",\"BANCOMEXT\":\"BANCOMEXT\",\"BANCOPPEL\":\"BANCOPPEL\",\"BANCO S3\":\"BANCO S3\",\"BANCREA\":\"BANCREA\",\"BANJERCITO\":\"BANJERCITO\",\"BANKAOOL\":\"BANKAOOL\",\"BANK OF AMERICA\":\"BANK OF AMERICA\",\"BANOBRAS\":\"BANOBRAS\",\"BANORTE\":\"BANORTE\",\"BANREGIO\":\"BANREGIO\",\"BANSI\":\"BANSI\",\"BANXICO\":\"BANXICO\",\"BARCLAYS\":\"BARCLAYS\",\"BBASE\":\"BBASE\",\"BBVA BANCOMER\":\"BBVA BANCOMER\",\"BMONEX\":\"BMONEX\",\"CAJA POP MEXICA\":\"CAJA POP MEXICA\",\"CAJA TELEFONIST\":\"CAJA TELEFONIST\",\"CB INTERCAM\":\"CB INTERCAM\",\"CIBANCO\":\"CIBANCO\",\"CI BOLSA\":\"CI BOLSA\",\"CLS\":\"CLS\",\"CoDi Valida\":\"CoDi Valida\",\"COMPARTAMOS\":\"COMPARTAMOS\",\"CONSUBANCO\":\"CONSUBANCO\",\"CREDICAPITAL\":\"CREDICAPITAL\",\"CREDIT SUISSE\":\"CREDIT SUISSE\",\"CRISTOBAL COLON\":\"CRISTOBAL COLON\",\"DONDE\":\"DONDE\",\"FINAMEX\":\"FINAMEX\",\"FINCOMUN\":\"FINCOMUN\",\"FOMPED\":\"FOMPED\",\"FONDO (FIRA)\":\"FONDO (FIRA)\",\"GBM\":\"GBM\",\"HIPOTECARIA FED\":\"HIPOTECARIA FED\",\"HSBC\":\"HSBC\",\"ICBC\":\"ICBC\",\"INBURSA\":\"INBURSA\",\"INDEVAL\":\"INDEVAL\",\"INMOBILIARIO\":\"INMOBILIARIO\",\"INTERCAM BANCO\":\"INTERCAM BANCO\",\"INVERCAP\":\"INVERCAP\",\"INVEX\":\"INVEX\",\"JP MORGAN\":\"JP MORGAN\",\"KUSPIT\":\"KUSPIT\",\"LIBERTAD\":\"LIBERTAD\",\"MASARI\":\"MASARI\",\"MIFEL\":\"MIFEL\",\"MIZUHO BANK\":\"MIZUHO BANK\",\"MONEXCB\":\"MONEXCB\",\"MUFG\":\"MUFG\",\"MULTIVA BANCO\":\"MULTIVA BANCO\",\"MULTIVA CBOLSA\":\"MULTIVA CBOLSA\",\"NAFIN\":\"NAFIN\",\"OPM\":\"OPM\",\"PAGATODO\":\"PAGATODO\",\"PROFUTURO\":\"PROFUTURO\",\"SABADELL\":\"SABADELL\",\"SANTANDER\":\"SANTANDER\",\"SCOTIABANK\":\"SCOTIABANK\",\"SHINHAN\":\"SHINHAN\",\"STP\":\"STP\",\"TACTIV CB\":\"TACTIV CB\",\"UNAGRA\":\"UNAGRA\",\"VALMEX\":\"VALMEX\",\"VALUE\":\"VALUE\",\"VECTOR\":\"VECTOR\",\"VE POR MAS\":\"VE POR MAS\",\"VOLKSWAGEN\":\"VOLKSWAGEN\"}','https://api.mpayonline.shop','[]');


alter table bank change code_num code_num varchar(50) DEFAULT '' COMMENT '银行代码';
insert into bank(`code`,`code_num`,`sort`,`status`,`name`,`shortname`,`created`,`updated`,`type`) values
          ('ABC CAPITAL','40138',0,'enabled','ABC CAPITAL','ABC CAPITAL','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('ACTINVER','40133',0,'enabled','ACTINVER','ACTINVER','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('AFIRME','40062',0,'enabled','AFIRME','AFIRME','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('AKALA','90638',0,'enabled','AKALA','AKALA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('ARCUS','90706',0,'enabled','ARCUS','ARCUS','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('ASP INTEGRA OPC','90659',0,'enabled','ASP INTEGRA OPC','ASP INTEGRA OPC','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('AUTOFIN','40128',0,'enabled','AUTOFIN','AUTOFIN','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('AZTECA','40127',0,'enabled','AZTECA','AZTECA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BaBien','37166',0,'enabled','BaBien','BaBien','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BAJIO','40030',0,'enabled','BAJIO','BAJIO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANAMEX','40002',0,'enabled','BANAMEX','BANAMEX','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANCO FINTERRA','40154',0,'enabled','BANCO FINTERRA','BANCO FINTERRA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANCOMEXT','37006',0,'enabled','BANCOMEXT','BANCOMEXT','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANCOPPEL','40137',0,'enabled','BANCOPPEL','BANCOPPEL','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANCO S3','40160',0,'enabled','BANCO S3','BANCO S3','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANCREA','40152',0,'enabled','BANCREA','BANCREA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANJERCITO','37019',0,'enabled','BANJERCITO','BANJERCITO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANKAOOL','40147',0,'enabled','BANKAOOL','BANKAOOL','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANK OF AMERICA','40106',0,'enabled','BANK OF AMERICA','BANK OF AMERICA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANOBRAS','37009',0,'enabled','BANOBRAS','BANOBRAS','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANORTE','40072',0,'enabled','BANORTE','BANORTE','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANREGIO','40058',0,'enabled','BANREGIO','BANREGIO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANSI','40060',0,'enabled','BANSI','BANSI','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BANXICO','2001',0,'enabled','BANXICO','BANXICO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BARCLAYS','40129',0,'enabled','BARCLAYS','BARCLAYS','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BBASE','40145',0,'enabled','BBASE','BBASE','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BBVA BANCOMER','40012',0,'enabled','BBVA BANCOMER','BBVA BANCOMER','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('BMONEX','40112',0,'enabled','BMONEX','BMONEX','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CAJA POP MEXICA','90677',0,'enabled','CAJA POP MEXICA','CAJA POP MEXICA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CAJA TELEFONIST','90683',0,'enabled','CAJA TELEFONIST','CAJA TELEFONIST','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CB INTERCAM','90630',0,'enabled','CB INTERCAM','CB INTERCAM','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CIBANCO','40143',0,'enabled','CIBANCO','CIBANCO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CI BOLSA','90631',0,'enabled','CI BOLSA','CI BOLSA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CLS','90901',0,'enabled','CLS','CLS','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CoDi Valida','90903',0,'enabled','CoDi Valida','CoDi Valida','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('COMPARTAMOS','40130',0,'enabled','COMPARTAMOS','COMPARTAMOS','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CONSUBANCO','40140',0,'enabled','CONSUBANCO','CONSUBANCO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CREDICAPITAL','90652',0,'enabled','CREDICAPITAL','CREDICAPITAL','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CREDIT SUISSE','40126',0,'enabled','CREDIT SUISSE','CREDIT SUISSE','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('CRISTOBAL COLON','90680',0,'enabled','CRISTOBAL COLON','CRISTOBAL COLON','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('DONDE','40151',0,'enabled','DONDE','DONDE','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('FINAMEX','90616',0,'enabled','FINAMEX','FINAMEX','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('FINCOMUN','90634',0,'enabled','FINCOMUN','FINCOMUN','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('FOMPED','90689',0,'enabled','FOMPED','FOMPED','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('FONDO (FIRA)','90685',0,'enabled','FONDO (FIRA)','FONDO (FIRA)','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('GBM','90601',0,'enabled','GBM','GBM','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('HIPOTECARIA FED','37168',0,'enabled','HIPOTECARIA FED','HIPOTECARIA FED','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('HSBC','40021',0,'enabled','HSBC','HSBC','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('ICBC','40155',0,'enabled','ICBC','ICBC','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('INBURSA','40036',0,'enabled','INBURSA','INBURSA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('INDEVAL','90902',0,'enabled','INDEVAL','INDEVAL','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('INMOBILIARIO','40150',0,'enabled','INMOBILIARIO','INMOBILIARIO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('INTERCAM BANCO','40136',0,'enabled','INTERCAM BANCO','INTERCAM BANCO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('INVERCAP','90686',0,'enabled','INVERCAP','INVERCAP','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('INVEX','40059',0,'enabled','INVEX','INVEX','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('JP MORGAN','40110',0,'enabled','JP MORGAN','JP MORGAN','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('KUSPIT','90653',0,'enabled','KUSPIT','KUSPIT','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('LIBERTAD','90670',0,'enabled','LIBERTAD','LIBERTAD','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('MASARI','90602',0,'enabled','MASARI','MASARI','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('MIFEL','40042',0,'enabled','MIFEL','MIFEL','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('MIZUHO BANK','40158',0,'enabled','MIZUHO BANK','MIZUHO BANK','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('MONEXCB','90600',0,'enabled','MONEXCB','MONEXCB','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('MUFG','40108',0,'enabled','MUFG','MUFG','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('MULTIVA BANCO','40132',0,'enabled','MULTIVA BANCO','MULTIVA BANCO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('MULTIVA CBOLSA','90613',0,'enabled','MULTIVA CBOLSA','MULTIVA CBOLSA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('NAFIN','37135',0,'enabled','NAFIN','NAFIN','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('OPM','90684',0,'enabled','OPM','OPM','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('PAGATODO','40148',0,'enabled','PAGATODO','PAGATODO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('PROFUTURO','90620',0,'enabled','PROFUTURO','PROFUTURO','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('SABADELL','40156',0,'enabled','SABADELL','SABADELL','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('SANTANDER','40014',0,'enabled','SANTANDER','SANTANDER','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('SCOTIABANK','40044',0,'enabled','SCOTIABANK','SCOTIABANK','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('SHINHAN','40157',0,'enabled','SHINHAN','SHINHAN','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('STP','90646',0,'enabled','STP','STP','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('TACTIV CB','90648',0,'enabled','TACTIV CB','TACTIV CB','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('UNAGRA','90656',0,'enabled','UNAGRA','UNAGRA','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('VALMEX','90617',0,'enabled','VALMEX','VALMEX','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('VALUE','90605',0,'enabled','VALUE','VALUE','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('VECTOR','90608',0,'enabled','VECTOR','VECTOR','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('VE POR MAS','40113',0,'enabled','VE POR MAS','VE POR MAS','2022-10-27 13:57:03','2022-10-27 13:57:03',1),
          ('VOLKSWAGEN','40141',0,'enabled','VOLKSWAGEN','VOLKSWAGEN','2022-10-27 13:57:03','2022-10-27 13:57:03',1);

#YOUPAY支付
INSERT INTO `pay_config` (`name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`, `max_money`, `ip`, `show_type`, `updated`, `status`, `money_stop`, `money_used`, `money_day_stop`, `money_day_used`, `sort`, `return_type`, `comment`, `link_data`,`pay_type`,`params`) VALUES ('YOUPAY', 'youpay', '10321', 'Ffks6n6DLUOhCo3YzOQDw', 'udhfZSJVS0GhHF_IMvzdRg', 'https://hum.pasdz.com', 0, 0, '52.229.165.160', 'h5', '2022-10-26 14:06:34', 'disabled', 0, 0, 0, 0, 9, 'json', NULL, '','','{"channelNo": 4}');

INSERT INTO level_online (level_id, pay_plat) VALUES(1,'youpay'),(2,'youpay'),(3,'youpay'),(4,'youpay'),(5,'youpay'),(6,'youpay'),(7,'youpay'),(8,'youpay');