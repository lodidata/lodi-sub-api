#修改支付日志表
ALTER TABLE `transfer_log`
    MODIFY COLUMN `response` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `json`;



##BPAY支付
INSERT INTO `pay_config` (`id`, `name`, `type`, `partner_id`, `key`, `pub_key`, `payurl`, `min_money`,`max_money`,`status`,`sort`,`ip`) 
VALUE(7,'BPAY','bpay','3018220428002','MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALGPu8pij5NXNm+hPb/Yrx9dRRABh8UgD65kdpAqLXgKdKPi41kzLeeNv1tLMlxTbUIF1Nyyrx9m2Hc2pUehz4ELAUpWHYgrISrhRP4n/HXyaSo8zAkXtrL4fTC28axxHd/3Ln0j/d8qXmp+e0FrT8seNmbNohhDXMvy9RyoiI3pAgMBAAECgYA8geuPmyisIBMn1T2Sq8d5m7IWMz9OGI/fcNLKa+UUvBNlacLpubwR5UbpWGWv+qoHzq7sCmQNAmIPtep6z5RDJiXwOY2f+sXDuCnBkN/JXe9Ie32p22QaF2UKdivyoe4O6pU9umwNmbCeVicThGzJZYLRSJUm4+N8GmWty/lfgQJBAOxNFyMZgZ4CxjzWH1VcQpdDuasnmaZl7QyIZ1FGBOyng0vPcn8eqkpQ+ErXJcfXkAEVyZSuSLFPFnCaB43G+hECQQDAXRP4BKNzRd2Wd0Oqbta6VnwIzAwGT2o8b3a/XNl2ZqXuwnSTfyi0YGQa41RT/oJFk86H9fu8h9WbR06Jk75ZAkEAh79tqENR3AU5/t7/VxlOQ/mrIvD36sipGkcOG3l/ALjmy1lcLDzglRrY2J2qXZivaIAsspZAumN1v7As4LzLEQJBAI9+336MN0GuRHYR0bA5roSiLzSAwheS9jTPEU3+/VmNiQpqlHvSx5KGtSY5npZprNQqRk61+GvTCE0lDDkktUECQQDp3KNLCS1L5ot0yIAMaIXd/G8xW5OYu1iQfKTqXNPBQ38bZUXt47EkS6Zp5zaCuAWF5h8y7qUtu2m1DR6+tMB1', 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCP2AcIGgbWBnK/eQKKT7wxQ0386S3av9VGGE16jdMQQD/2v98wqYWfLA12vP4KTrPmFwx1WaaDuXAQQ11BVnSHtyJxOwicNquSkWdaDLwyk6qUpctMdzva7fLWHgJL9E5/nVyBQJQ4Vwzklbtn0VRpNx8Ap/Dayeg1HfMJivaGWwIDAQAB', 'https://api.bpay.im', 0, 0, 'enabled', 12,'3.7.249.92,3.111.54.91');

INSERT INTO `funds_channel` (`type_id`,`title`,`desc`, `show`) VALUE(7, 'BPAY', 'BPAY', 'online');

INSERT INTO `level_online` (`level_id`, `pay_plat`) VALUES(1, 'bpay'),(2, 'bpay'),(3, 'bpay'),(4, 'bpay'),(5, 'bpay'),(6, 'bpay'),(7, 'bpay'),(8, 'bpay');


#NGPAY支付,代付
INSERT INTO `transfer_config` (
    `name`,
    `balance`,
    `code`,
    `ver`,
    `app_id`,
    `app_secret`,
    `sort`,
    `status`,
    `key`,
    `pub_key`,
    `token`,
    `terminal`,
    `partner_id`,
    `url_notify`,
    `url_return`,
    `max_money`,
    `min_money`,
    `fee`,
    `email`,
    `request_code`,
    `bank_list`,
    `request_url`
)
VALUES
(
    'NGPAY',
    10000000,
    'NGPAY',
    NULL,
    '0',
    '@PP(>*2HHEIK3X9G9BJU',
    1,
    'enabled',
    'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiZGQ4ZTlhYTVkZWM4YTE3NWQzYzdjOTJkMmFmMmY2MDM4YmRmZTVmN2YyMWM2NGI2OTI1NDRkN2M1YTEwMDQ1NjdjM2M2MjI4YTNiMWU1MDMiLCJpYXQiOjE2NTExMzg0MzQsIm5iZiI6MTY1MTEzODQzNCwiZXhwIjoxOTY2NzU3NjM0LCJzdWIiOiIxMCIsInNjb3BlcyI6W119.fcCY5Gkz0IWC7gEwgr6J2Sxbv0shsIgyZzJUeDXC_gDYf8Bnm11q7bA9PIoDvV_sMAY5muQ3KIo6P2LIMwFQERhA5imKgWN35L4aqUO-lhG2MjYfxTSfl-kQzW-TyOsatCC4MsD4Uf2altjbHYQbUDGfGlrvIOUpyqxAqqaJLty38OQNWkd1z5G1EIURjn4bPaii_O5sHVJs9UtQhXk4GvWG6s488_qUDEqk5GoEqgz-qigRIdeQMFjdfNm66TVKqtPDTQh8Z1c13fnCn6o7uT1BSJY6ytLxp75tKAfu7Fr3jCOOCeW2pvtZfq9nQttH0XsfigFTeIno6WrDzNkUqB_ofsFPi-d6oPsgHOqatsO2NK5DUg0TvmcRFdgTJQt54Z57yijxUtGixeYFlIBjg4fLN398euC9N37vJTwXFaIZoJ_zNIEQSL_tlVXePldjE6c8-CWUboCiEpQYm1AmGZzqwUW2XyvYZv5KxpCBcvf3bPbsge5zdQI10jiqf3QbmBtMifwYSmI9gu0gVW4OuTpzJUJ8LBpwRtT0bMlGAGO_fZfzIYitypl497WC7LUG0L6CWu0cKW4dpYaZMLSMHr605UbicN2_6POW-MxDmCh2e9Ooegqt9kQAsP1X56_GbjOHidUL5Pw6dMzABNRzZ5X7vrGMFRhiRKG34y7fG3s',
    'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiZGQ4ZTlhYTVkZWM4YTE3NWQzYzdjOTJkMmFmMmY2MDM4YmRmZTVmN2YyMWM2NGI2OTI1NDRkN2M1YTEwMDQ1NjdjM2M2MjI4YTNiMWU1MDMiLCJpYXQiOjE2NTExMzg0MzQsIm5iZiI6MTY1MTEzODQzNCwiZXhwIjoxOTY2NzU3NjM0LCJzdWIiOiIxMCIsInNjb3BlcyI6W119.fcCY5Gkz0IWC7gEwgr6J2Sxbv0shsIgyZzJUeDXC_gDYf8Bnm11q7bA9PIoDvV_sMAY5muQ3KIo6P2LIMwFQERhA5imKgWN35L4aqUO-lhG2MjYfxTSfl-kQzW-TyOsatCC4MsD4Uf2altjbHYQbUDGfGlrvIOUpyqxAqqaJLty38OQNWkd1z5G1EIURjn4bPaii_O5sHVJs9UtQhXk4GvWG6s488_qUDEqk5GoEqgz-qigRIdeQMFjdfNm66TVKqtPDTQh8Z1c13fnCn6o7uT1BSJY6ytLxp75tKAfu7Fr3jCOOCeW2pvtZfq9nQttH0XsfigFTeIno6WrDzNkUqB_ofsFPi-d6oPsgHOqatsO2NK5DUg0TvmcRFdgTJQt54Z57yijxUtGixeYFlIBjg4fLN398euC9N37vJTwXFaIZoJ_zNIEQSL_tlVXePldjE6c8-CWUboCiEpQYm1AmGZzqwUW2XyvYZv5KxpCBcvf3bPbsge5zdQI10jiqf3QbmBtMifwYSmI9gu0gVW4OuTpzJUJ8LBpwRtT0bMlGAGO_fZfzIYitypl497WC7LUG0L6CWu0cKW4dpYaZMLSMHr605UbicN2_6POW-MxDmCh2e9Ooegqt9kQAsP1X56_GbjOHidUL5Pw6dMzABNRzZ5X7vrGMFRhiRKG34y7fG3s',
    '',
    NULL,
    'HCCPTG29678',
    NULL,
    NULL,
    0,
    0,
    0,
    NULL,
    NULL,
    '{\"Gcash\":\"Gcash\"}',
    'https://api.nippongateway.net'
);




#BPAY支付,代付
INSERT INTO `transfer_config` (
    `name`,
    `balance`,
    `code`,
    `ver`,
    `app_id`,
    `app_secret`,
    `sort`,
    `status`,
    `key`,
    `pub_key`,
    `token`,
    `terminal`,
    `partner_id`,
    `url_notify`,
    `url_return`,
    `max_money`,
    `min_money`,
    `fee`,
    `email`,
    `request_code`,
    `bank_list`,
    `request_url`
)
VALUES
(
    'BPAY',
    0,
    'BPAY',
    NULL,
    '0',
    NULL,
    1,
    'enabled',
    'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALGPu8pij5NXNm+hPb/Yrx9dRRABh8UgD65kdpAqLXgKdKPi41kzLeeNv1tLMlxTbUIF1Nyyrx9m2Hc2pUehz4ELAUpWHYgrISrhRP4n/HXyaSo8zAkXtrL4fTC28axxHd/3Ln0j/d8qXmp+e0FrT8seNmbNohhDXMvy9RyoiI3pAgMBAAECgYA8geuPmyisIBMn1T2Sq8d5m7IWMz9OGI/fcNLKa+UUvBNlacLpubwR5UbpWGWv+qoHzq7sCmQNAmIPtep6z5RDJiXwOY2f+sXDuCnBkN/JXe9Ie32p22QaF2UKdivyoe4O6pU9umwNmbCeVicThGzJZYLRSJUm4+N8GmWty/lfgQJBAOxNFyMZgZ4CxjzWH1VcQpdDuasnmaZl7QyIZ1FGBOyng0vPcn8eqkpQ+ErXJcfXkAEVyZSuSLFPFnCaB43G+hECQQDAXRP4BKNzRd2Wd0Oqbta6VnwIzAwGT2o8b3a/XNl2ZqXuwnSTfyi0YGQa41RT/oJFk86H9fu8h9WbR06Jk75ZAkEAh79tqENR3AU5/t7/VxlOQ/mrIvD36sipGkcOG3l/ALjmy1lcLDzglRrY2J2qXZivaIAsspZAumN1v7As4LzLEQJBAI9+336MN0GuRHYR0bA5roSiLzSAwheS9jTPEU3+/VmNiQpqlHvSx5KGtSY5npZprNQqRk61+GvTCE0lDDkktUECQQDp3KNLCS1L5ot0yIAMaIXd/G8xW5OYu1iQfKTqXNPBQ38bZUXt47EkS6Zp5zaCuAWF5h8y7qUtu2m1DR6+tMB1',
    'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCP2AcIGgbWBnK/eQKKT7wxQ0386S3av9VGGE16jdMQQD/2v98wqYWfLA12vP4KTrPmFwx1WaaDuXAQQ11BVnSHtyJxOwicNquSkWdaDLwyk6qUpctMdzva7fLWHgJL9E5/nVyBQJQ4Vwzklbtn0VRpNx8Ap/Dayeg1HfMJivaGWwIDAQAB',
    NULL,
    NULL,
    '3018220428002',
    NULL,
    NULL,
    0,
    0,
    0,
    NULL,
    NULL,
    '{\"Gcash\":\"Gcash\"}',
    'https://api.bpay.im'
);


