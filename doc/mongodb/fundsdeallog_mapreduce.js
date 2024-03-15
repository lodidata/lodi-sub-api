var map = function() {
    var sb = {
        '101': this.deal_type == 101 ? this.deal_money : 0,
        '102': this.deal_type == 102 ? this.deal_money : 0,
        '103': this.deal_type == 103 ? this.deal_money : 0,
        '104': this.deal_type == 104 ? this.deal_money : 0,
        '105': this.deal_type == 105 ? this.deal_money : 0,
        '106': this.deal_type == 106 ? this.deal_money : 0,
        '107': this.deal_type == 107 ? this.deal_money : 0,
        '108': this.deal_type == 108 ? this.deal_money : 0,
        '109': this.deal_type == 109 ? this.deal_money : 0,
        '110': this.deal_type == 110 ? this.deal_money : 0,
        '111': this.deal_type == 111 ? this.deal_money : 0,
        '112': this.deal_type == 112 ? this.deal_money : 0,
        '113': this.deal_type == 113 ? this.deal_money : 0,
        '114': this.deal_type == 114 ? this.deal_money : 0,
        '115': this.deal_type == 115 ? this.deal_money : 0,
        '116': this.deal_type == 116 ? this.deal_money : 0,
        '117': this.deal_type == 117 ? this.deal_money : 0,
        '201': this.deal_type == 201 ? this.deal_money : 0,
        '202': this.deal_type == 202 ? this.deal_money : 0,
        '203': this.deal_type == 203 ? this.deal_money : 0,
        '204': this.deal_type == 204 ? this.deal_money : 0,
        '205': this.deal_type == 205 ? this.deal_money : 0,
        '206': this.deal_type == 206 ? this.deal_money : 0,
        '207': this.deal_type == 207 ? this.deal_money : 0,
        '208': this.deal_type == 208 ? this.deal_money : 0,
        '209': this.deal_type == 209 ? this.deal_money : 0,
        '210': this.deal_type == 210 ? this.deal_money : 0,
        '301': this.deal_type == 301 ? this.deal_money : 0,
        '302': this.deal_type == 302 ? this.deal_money : 0,
        '303': this.deal_type == 303 ? this.deal_money : 0,
        '304': this.deal_type == 304 ? this.deal_money : 0,
        '501': this.deal_type == 501 ? this.deal_money : 0,
        '502': this.deal_type == 502 ? this.deal_money : 0,
        '503': this.deal_type == 503 ? this.deal_money : 0,
        '601': this.deal_type == 601 ? this.deal_money : 0
    };
    emit(this.user_id, sb);
};

var reduce = function(key, val) {
    reduceValue = {
        '101': 0,
        '102': 0,
        '103': 0,
        '104': 0,
        '105': 0,
        '106': 0,
        '107': 0,
        '108': 0,
        '109': 0,
        '110': 0,
        '111': 0,
        '112': 0,
        '113': 0,
        '114': 0,
        '115': 0,
        '116': 0,
        '117': 0,
        '201': 0,
        '202': 0,
        '203': 0,
        '204': 0,
        '205': 0,
        '206': 0,
        '207': 0,
        '208': 0,
        '209': 0,
        '210': 0,
        '301': 0,
        '302': 0,
        '303': 0,
        '304': 0,
        '501': 0,
        '502': 0,
        '503': 0,
        '601': 0
    };

    for (var i = 0; i < val.length; i++) {
        reduceValue['101'] += val[i]['101'],
            reduceValue['102'] += val[i]['102'],
            reduceValue['103'] += val[i]['103'],
            reduceValue['104'] += val[i]['104'],
            reduceValue['105'] += val[i]['105'],
            reduceValue['106'] += val[i]['106'],
            reduceValue['107'] += val[i]['107'],
            reduceValue['108'] += val[i]['108'],
            reduceValue['109'] += val[i]['109'],
            reduceValue['110'] += val[i]['110'],
            reduceValue['111'] += val[i]['111'],
            reduceValue['112'] += val[i]['112'],
            reduceValue['113'] += val[i]['113'],
            reduceValue['114'] += val[i]['114'],
            reduceValue['115'] += val[i]['115'],
            reduceValue['116'] += val[i]['116'],
            reduceValue['117'] += val[i]['117'],
            reduceValue['201'] += val[i]['201'],
            reduceValue['202'] += val[i]['202'],
            reduceValue['203'] += val[i]['203'],
            reduceValue['204'] += val[i]['204'],
            reduceValue['205'] += val[i]['205'],
            reduceValue['206'] += val[i]['206'],
            reduceValue['207'] += val[i]['207'],
            reduceValue['208'] += val[i]['208'],
            reduceValue['209'] += val[i]['209'],
            reduceValue['210'] += val[i]['210'],
            reduceValue['301'] += val[i]['301'],
            reduceValue['302'] += val[i]['302'],
            reduceValue['303'] += val[i]['303'],
            reduceValue['304'] += val[i]['304'],
            reduceValue['501'] += val[i]['501'],
            reduceValue['502'] += val[i]['502'],
            reduceValue['503'] += val[i]['503'],
            reduceValue['601'] += val[i]['601']
    }
    return reduceValue;
};

db.funds_deal_log_28.mapReduce(map, reduce, {
    out: {
        replace: "sb"
    },
    //     query: {user_id: 128}
    sort: {
        _id: -1
    },
    jsMode: true
}).find();


//db.sb.count();
db.funds_deal_log_28.find({
    user_id: 128
});