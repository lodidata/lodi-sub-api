<?php 

namespace LotteryPlay;

class Context {
    
    public $contextData = [];

    protected $_next = true;
    
    protected $_reason = '';
    
    public function stopNext() {
        $this->_next = false;
        return $this;
    }

    public function allowNext() {
        return $this->_next;
    }

    public function getReason() {
        return $this->_reason;
    }

    public function setReason($reason) {
        $this->_reason = $reason;
        return $this;
    }
}