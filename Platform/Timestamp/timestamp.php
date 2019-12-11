<?php
namespace Platform;

class Timestamp {
    
    private $timestamp = null;
    
    private static $time_zone_object = null;
    
    public function __construct($ts = null) {
        if ($ts instanceof Timestamp) $this->timestamp = $ts->getTimestamp();
        elseif ($ts == 'now') $this->timestamp = time();
        elseif ($ts) $this->timestamp = strtotime($ts);
    }
    
    public function add($seconds, $minutes = 0, $hours = 0) {
        $this->timestamp += $seconds + $minutes*60 + $hours*3600;
        return $this;
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

    public function getReadable($format = 'd-m-Y H:i') {
        if ($this->timestamp !== null) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($this->timestamp);
            if (self::$time_zone_object) $datetime->setTimezone(self::$time_zone_object);
            return $datetime->format($format);
        }
        return '';
    }
    
    public function getMinutesUntil($other_timestamp) {
        $other_timestamp = new Timestamp($other_timestamp);
        if ($other_timestamp->getTimestamp() === null || $this->getTimestamp() === null) return false;
        return floor(($other_timestamp->getTimestamp()-$this->getTimestamp())/60);
    }
    
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    public function getTime($format = 'Y-m-d H:i:s') {
        return $this->timestamp !== null ? date($format, $this->timestamp) : null;
    }
    
    public function isAfter($othertimestamp) {
        $othertimestamp = new Timestamp($othertimestamp);
        return $this->timestamp > $othertimestamp->getTimestamp();
    }
    
    public function isBefore($othertimestamp) {
        $othertimestamp = new Timestamp($othertimestamp);
        return $this->timestamp < $othertimestamp->getTimestamp();
    }
    
    public static function now() {
        return new Timestamp('now');
    }
    
    public static function setDisplayTimeZone($display_time_zone) {
        self::$time_zone_object = new \DateTimeZone($display_time_zone);
    }
    
}