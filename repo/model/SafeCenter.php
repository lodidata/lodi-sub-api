<?php
namespace Model;

class SafeCenter extends \Illuminate\Database\Eloquent\Model {
    
    protected $table = 'safe_center';

    public $timestamps = false;
    
    protected $fillable = [                
                            'user_id',           
                            'user_type',         
                            'id_card',           
                            'withdraw_password', 
                            'mobile',            
                            'email',             
                            'question',          
                            'dynamic',
                            'bank_card',
                        ];
}

