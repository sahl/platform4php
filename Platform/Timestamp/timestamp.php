<?php
namespace Platform;

class Timestamp {
    
    private $timestamp = null;
    
    
    public function __construct($ts = null) {
        if ($ts instanceof Timestamp) $this->timestamp = $ts->getTimestamp();
        elseif ($ts == 'now') $this->timestamp = time();
        elseif ($ts) $this->timestamp = strtotime($ts);
    }
    
    public function add($seconds, $minutes = 0, $hours = 0) {
        $this->timestamp += $seconds + $minutes*60 + $hours*3600;
        return $this;
    }
    
    public function isAfter($othertimestamp) {
        $othertimestamp = new Timestamp($othertimestamp);
        return $this->timestamp > $othertimestamp->getTimestamp();
    }
    
    public function isBefore($othertimestamp) {
        $othertimestamp = new Timestamp($othertimestamp);
        return $this->timestamp < $othertimestamp->getTimestamp();
    }
    
    public function getReadable($format = 'd-m-Y H:i') {
        return $this->timestamp !== null ? date($format, $this->timestamp) : '';
    }
    
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    public function getTime($format = 'Y-m-d H:i:s') {
        return $this->timestamp !== null ? date($format, $this->timestamp) : null;
    }
    
    /**
     * Check if this timestamp is equal to another timestamp
     * @param Timestamp $ts Other timestamp
     * @return boolean
     */
    public function equalTo($ts) {
        if (! $ts instanceof Timestamp) return false;
        return $this->timestamp == $ts->getTimestamp();
    }
}