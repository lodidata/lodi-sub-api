<?php
namespace Model;

class Answer extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'answer';

    public $timestamps = false;

    protected $fillable = [
                              'id',
                              'user_id',
                              'user_type',
                              'question_id',
                              'answer',
                              'updated',
                              'created',
                        ];

    public static function boot() {
        parent::boot();
    }

    public static function getRecords($userId) {
        $data = DB::table('answer')->leftjoin('question', 'answer.question_id', '=', 'question.id')
                ->where('answer.user_type', 1)
                ->where('answer.user_id', $userId)
                ->selectRaw('answer.answer, question.question')
                ->get()
                ->toArray();
        return $data;
    }
}


