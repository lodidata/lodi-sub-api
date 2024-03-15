<?php
/* 10000 - 19999*/
return [
    -3 => 'China IP is forbidden to login',
    -2 => 'Operation failed',
    -1 => 'program debugging',
    1 => ' Login successfully|200',
    2 => 'Successfully exit|200',
    3 => 'Added successfully|200',
    4 => 'Order placed successfully|200',
    10011 => 'Please login first|401',

    131 => 'The rebate rate cannot exceed the set value',
    132 => 'The rebate rate cannot be less than the top subordinate',
    133 => ' Add user failed',
    134 => ' Agency promotion information add failed',
    135 => 'Successfully added agency promotion information|200',

    201 => "You have participated in the giveaway, Can't fit into this category",
    202 => "Bonus claimed successfully,credited to wallet ",
    203 => "Unable to claim,bonus expired",
    204 => "Bonus claimed",
    205 => 'Each deposit %s can get bonus',
    206 => 'Accumulate deposit reach %s can get bonus',
    207 => 'Duration time should not more than 1 month',

    701 => 'The number of exported items cannot exceed 50000. Contact the administrator for SQL export',

    886 => '%s',

    9001 => 'Incorrect draw format',
    9002 => ' Not within the draw time , Cannot be changed,Already expired:%seconds',
    9011 => ' Modification failed， Remain at least one play method',

    5002 => ' Not implemented or unprocessable',

    10000 => ' upload failed',
    10001 => 'System Error：%s，Please try again later！',
    10010 => 'Missing or incorrect parameter',
    10012 => '%sParameter Invalid',
    10013 => 'Parameter error：%s',
    10014 => 'User does not exist！',
    10015 => 'data does not exist！',
    10016 => 'Add room failed,This room ID already exists',
    10017 => 'The live room for this lottery does not exist',
    10018 => 'Room id cannot be empty',
    10019 => 'Character name already exists',
    10020 => 'The character is in use,Cannot be deleted！',
    10021 => 'Official users cannot change to test users！',
    10022 => 'Test users cannot change to Official users！',
    10200 => 'Operation successful|200',
    10030 => 'Test and demo users are not allowed to change tags ！',
    10031 => 'This user belongs to the agency team，Do not allow random changes to testing and demos！',
    10032 => 'Event time is wrong，Start time cannot be greater than end time',
    10033 => 'The user has subordinate，Cannot be closed',
    10034 => "The user's agent has not set profit and loss.",
    10035 => '%s category downline comm. ratio should not lower than own %s',
    10036 => '%s category downline comm. ratio should not higher than own %s',
    10037 => '%s category Ratio difference with upline must be greater than 1',

    10041 => 'Login information has been changed，Please login again！|401',
    10042 => 'Login timed out，Please login again！|401',
    10043 => 'You do not have permission for this operation！|401',
    10044 => 'Your IP cannot login to the system',
    10045 => 'Incorrect verification code，Please try again！',
    10046 => 'Login failed,Wrong username or password,Please modify and try again！',
    10047 => 'Google verification code does not match！',

    10401 => "you don't have authorization to edit！",
    10402 => 'there are members in this level, cannot remove！',
    10403 => 'add new level failed',
    10404 => 'edit level payment failed',
    10405 => 'level classification failed',
    10406 => 'this %s data already exist',
    10407 => 'this level name already exist',
    10408 => 'this level condition already exist ',
    10409 => 'there are no eligible level or no met with previous conditions  ',
    10410 => 'current odds rate cannot greater than highest odds rate',
    10411 => 'total bet amount limit of single round cannot less than bet amount limit of individual single round',
    10412 => 'bet amount limit of individual single round cannot less than total bet amount limit of single round ',
    10413 => "lottery doesn't exist！",
    10414 => "hall doesn't exist！",
    10415 => 'bet amount limit of individual single round cannot less than bet amount limit of hall single round ',
    10416 => 'both lottery and ID cannot be blank',
    10417 => 'start date cannot more than end date',
    10418 => 'odds rate or return rate cannot be 0！',
    10419 => 'this rebate bonus percentage cannot be set, current rebate payback rate are 0 ！',
    10420 => 'The proportion can not be less than 0',
    10421 => 'The ratio is being reset, please try again later~',
    10422 => 'The current level 1 agent has enabled the fixed ratio, and the default ratio can no longer be enabled. If necessary, please turn off the fixed ratio first',

    10500 => 'Applied successfully for third-party payment, waiting for transfer|200',
    10501 => 'There is no such third-party payment or the configuration data of the third-party payment is incomplete',
    10502 => 'Third-party payment does not support transfer to this bank',
    10503 => 'An error occured in the third-party payment request process, please contact the technical staff',
    10504 => 'Transfer amount range %s -- %s',
    10508 => 'Third-party payment and transfer successful | 200',
    10509 => 'Third-party payment balance query successful | 200',
    10511 => 'Third-party payment does not exist',
    10513 => 'Third-party payment order number does not exist',
    10514 => 'Order is being paid',
    10515 => 'Order has been paid successfully',
    10516 => 'Order is being transmitted to the  third party, please wait 10 seconds before proceeding',
    10517 => 'Order has been processed, please refresh',
    10544 => 'Third-party payment abnormal - %s',
    10535 => 'Card information is incorrect or not in the third-party IP whitelist, please confirm with the third-party',

    10545 => 'Order number cannot be empty',
    10546 => 'Failed to get third-party account balance: %s',
    10547 => 'Up to 5 bank cards can be enabled',
    10548 => 'Bad card format error',
    10549 => 'This account has been deactivated!',
    10550 => 'Invalid deposit',
    10551 => 'Event is still in process！Please do not delete!',
    10552 => 'Withdrawable balance cannot be greater than the main wallet balance',
    10553 => 'Minimum balance that can be withdrawn is 0',
    10554 => 'Maximum balance has been reached, increase the turnover before performing this transaction.',
    10555 => 'Withdrawable amount must be an integar',
    10557 => 'No modification for agent change!',
    10558 => 'User himself cannot be selected as top agent!!',
    10559 => '%s is already the top agent of this user!',


    10580 => 'Im messenger request for authentication had failed',
    10581 => 'Im messenger request for parameter verification mistake:%s',
    10582 => 'customer service status in customer service manager must be disable before delete this manager account',

    10560 => 'the withdrawal request already proceed！|200',

    10570 => 'self draw reward return rate interval: the difference must >=20%! ',
    10571 => 'please set interval of self draw reward return rate correctly！',
    10572 => 'number of draw result is wrong！',
    10573 => 'current period already ended, cannot cancel manual draw result！',

    10610 => 'current IP already exist！',
    10611 => 'IP cannot be blank！',
    10612 => 'plese enter correct IP format ',
    10613 => 'remark content cannot more than 100 digit',
    10614 => 'your IP address cannot log in to the system (white list IP address)',
    10615 => 'description content cannot more than 500 digit',

    10800 => 'token authentication failed',
    10801 => "record doesn't exist",
    10898 => 'bet amount or minimum recharge amount cannot greater than level %s',
    10899 => 'only when upline recharge amount is 0, then downline recharge amount can be set to 0 ',
    10900 => 'only when upline bet amount is 0, then downline bet amount can be set to 0 ',
    10901 => 'member level are default setting, cannot add or edit',
    10902 => 'bet amount and minimum recharge amount must greater than level %s',
    10903 => 'bet amount and minimum recharge amount must less than level %s',

    /*=======zt===lucky spin event===start=====*/
    10810 => "event hasn't end yet, forbid to delete! ",
    10811 => "this kind of event does't exist or it already ended！",
    10812 => 'Your current draws has been used up and cannot participate in the spin',
    10813 => '%s must be positive round number！',
    10814 => 'winning percentage cannot less than 0！',
    10815 => 'Your current level does not meet the requirement for participating in the spin',
    10816 => 'Lucky Spin',
    10817 => 'Congratulations on your winning %s bonus in the Lucky Spin',
    10818 => 'Congratulations on your winning %s, please contact customer service to claim',
    /*=======zt===lucky spin event===end=====*/

    11010 => 'please add at least one white list IP address',
    11020 => 'request failed, try again later (%s)',
    11021 => 'one time transaction amount cannot exceed 500k',

    /****************上传*******************/
    11022 => "uploaded file size doesn't match",
    11023 => "uploaded file suffix doesn't match",

    11024 => 'Apply Agent',
    11025 => 'Your agent application is successful',
    11026 => 'Apply Agent',
    11027 => 'Your application was rejected.',
    11028 => 'Has to be numbers',
    11029 => 'Length not less than 8 digits',
    11030 => 'Length not more than 15 digits',
    11031 => 'Comments and feedback',
    11032 => 'Thank you for your feedback, please go to My Feedback to see the reply',

    11040 => 'The password must be included of numbers, uppercase and lowercase or special characters, between 8-16 characters',
    11041 => 'Please enter the correct jump link',
    11050 => 'Please reset the initial password',
    11051 => 'Please reset the Pin password',
    11052 => 'Current manual deposit is exceed limit,please wait for approval',
    11053 => 'pin password is incorrect',

    11054 => 'The current exported data volume exceeds 5,000 bytes, Please contact the DBA for processing or export in batches.',

    11060 => 'The no. of registrants must be greater than the no. of registrants at the previous level',
    11061 => 'The no. of depositors must be greater than the no. of depositors at the previous level',
    11062 => 'The rebate increase ratio must be greater than the previous level rebate increase ratio',
    11063 => "This log can't be deleted temporarily, please proceed step by step",
    11055 => 'The minimum bank card limit cannot be less than the cash withdrawal limit',
    11056 => 'The maximum bank card limit cannot be greater than the cash withdrawal limit',
    11064 => 'Sorting cannot be same',

    11160 => 'Current channel app generated',
    11101 => 'The channel number does not exist',
    11170 => 'This batch no one received',
    11171 => 'This batch has issued rebate',
    11057 => 'The minimum amount is greater than the maximum amount, please reset',
    11067 => '周返水提升比例必须大于上一级返水提升比例',
    11068 => '月返水提升比例必须大于上一级返水提升比例',
    11069 => '日返水提升比例必须小于下一级返水提升比例',
    11070 => '周返水提升比例必须小于下一级返水提升比例',
    11071 => '月返水提升比例必须小于下一级返水提升比例',

    11065 => 'Please upload the full package version for Android and iOS before generating',
    11066 => 'The transfer amount cannot be greater than or equal to 100000',

    11067 => 'The single withdrawal limit cannot be greater than the daily withdrawal limit',
];