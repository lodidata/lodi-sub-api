<?php

namespace Model;

use Illuminate\Database\Eloquent\Model;

class ChannelDownload extends Model
{

    protected $table = 'channel_download';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    const DELETE_IS_NOT = 0;
    const CHANNEL_DOWNLOAD_DEFAULT = 'default';

    protected $fillable = [
        'id',
        'channel_no',
        'channel_name',
        'product_name',
        'download_url',
        'H5_url',
        'android',
        'ios',
        'super_label',
        'super_label_state',
        'enterprise_label',
        'enterprise_label_state',
        'TF_label',
        'TF_label_state',
        'icon_url',
        'bottom_text',
        'is_delete',
        'update_time',
        'create_time',
    ];

}


