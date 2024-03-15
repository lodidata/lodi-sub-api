db.getCollection('lottery_order').ensureIndex({
    'order_number': -1
}, {
    'unique': true,
    'background': true
});

db.getCollection('lottery_order').ensureIndex({
    'user_tags': -1,
    'chase_number': -1,
    'state': -1,
    'lottery_number': -1,
    'lottery_id': -1,
    'user_id': -1,
    'origin': -1
}, {
    'background': true

});