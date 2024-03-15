# 已结算订单投注额、 派奖、 输赢
db.getCollection('lottery_order').aggregate([{
        $match: {
            chase_number: 0,
            user_tags: {
                $nin: [4, 7]
            },
            state: {
                $in: ['open']
            }
        }
    }, {
        $group: {
            _id: null,
            payMoneyTotal: {
                $sum: "$pay_money"
            },
            sendMoneyTotal: {
                $sum: "$p_money"
            },
            profitTotal: {
                $sum: "$lost_earn"
            }
        }
    }

])

# 未结算订单投注额
db.getCollection('lottery_order').aggregate([{
    $match: {
        chase_number: 0,
        user_tags: {
            $nin: [4, 7]
        },
        state: {
            $nin: ['open']
        }
    }
}, {
    $group: {
        _id: null,
        payMoneyTotal: {
            $sum: "$pay_money"
        },
    }
}])

# 追号统计
db.getCollection('lottery_chase').aggregate([{
    $match: {
        user_tags: {
            $nin: [4, 7]
        }
    }
}, {
    $group: {
        _id: null,
        payMoneyTotal: {
            $sum: "$increment_bet"
        },
        sendMoneyTotal: {
            $sum: "$send_money"
        },
        profitTotal: {
            $sum: "$profit"
        }
    }
}])