<?php
namespace Model;

class ServiceList extends \Illuminate\Database\Eloquent\Model {
    
    protected $table = 'service_list';

    public $timestamps = false;
    
    protected $fillable = [
                            'id',
                            'question',
                            'answer',
                            'language_id',
                        ];
}

