<?php
namespace Model;

class Question extends \Illuminate\Database\Eloquent\Model {
    
    protected $table = 'question';

    public $timestamps = false;
    
    protected $fillable = [
                            'id',
                            'question',
                        ];

}




