
#会员用户权限设置维护可进
ALTER TABLE `user`
MODIFY COLUMN `auth_status` set('refuse_withdraw','refuse_sale','refuse_rebate','refuse_deposit_coupon','refuse_bkge','maintaining_login') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'refuse_withdraw(禁止提款),refuse_sale(禁止优惠),refuse_rebate(禁止返水),refuse_deposit_coupon(不要充值优惠),refuse_bkge(禁止返佣),maintaining_login(维护可进)' AFTER `last_login`;