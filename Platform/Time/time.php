<?php
namespace Platform;

class Time {
    
    /**
     * Internal representation as a timestamp
     * @var int
     */
    private $timestamp = null;
    
    /**
     * Timezone object for current time zone
     * @var string
     */
    private static $time_zone_object = null;
    
    /**
     * Construct a new Time object
     * @param mixed $time Either another time object to copy, a time string, "now" for current time or "today" for today at midnight.
     */
    public function __construct($time = null) {
        Errorhandler::checkParams($time, array('string', '\\Platform\\Time'));
        if ($time instanceof Time) $this->timestamp = $time->getTimestamp();
        elseif ($time == 'now') $this->timestamp = time();
        elseif ($time == 'today') $this->timestamp = strtotime(self::now()->get('Y-m-d'));
        elseif ($time) $this->timestamp = strtotime($time);
    }
    
    /**
     * Add to the time part of the datestamp
     * @param int $seconds Seconds to add
     * @param int $minutes Minutes to add
     * @param int $hours Hours to add
     * @return $this
     */
    public function add($seconds, $minutes = 0, $hours = 0) {
        Errorhandler::checkParams($seconds, 'int', $minutes, 'int', $hours, 'int');
        $this->timestamp += $seconds + $minutes*60 + $hours*3600;
        return $this;
    }
    
    /**
     * Add to the date part of the timestamp
     * @param int $days Days to add
     * @param int $months Months to add
     * @param int $years Years to add
     */
    public function addDays($days, $months = 0, $years = 0) {
        Errorhandler::checkParams($days, 'int', $months, 'int', $years, 'int');
        if ($months || $years) {
            // We handle this a little special
            $newyear = date('Y', $this->timestamp)+$years;
            $newmonth = date('n', $this->timestamp)+$months;
            $currentday = date('j', $this->timestamp);
            if ($currentday > self::daysInMonth($newmonth, $newyear)) $currentday = self::daysInMonth ($newmonth, $newyear);
            $this->timestamp = mktime(date('H', $this->timestamp), date('i', $this->timestamp), date('s', $this->timestamp), $newmonth, $currentday);
        }
        $this->timestamp += 24*60*60*$days;
        return $this;
    }
    
    /**
     * Check if this timestamp is equal to another timestamp
     * @param Time $other_time Other time
     * @return boolean
     */
    public function equalTo($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        return $this->timestamp == $other_time->getTimestamp();
    }
    
    /**
     * Get the number of days in the given month
     * @param int $month Month
     * @param int $year Year
     * @return int Number of days
     */
    public static function daysInMonth($month, $year) {
        Errorhandler::checkParams($month, 'int', $year, 'int');
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        return date('t', $timestamp);
    }
    
    /**
     * Get this time as a string
     * @param string $format Format to use
     * @return string
     */
    public function get($format = 'Y-m-d H:i:s') {
        Errorhandler::checkParams($format, 'string');
        return $this->timestamp !== null ? date($format, $this->timestamp) : null;
    }

    /**
     * Get date
     * @return string
     */
    public function getDate() {
        return date('Y-m-d', $this->timestamp);
    }
    
    /**
     * Get day
     * @return int
     */
    public function getDay() {
        return date('j', $this->timestamp);
    }
    
    /**
     * Get the number of days until another time
     * @param \Platform\Time $other_time
     * @return boolean|int Number of days or false if cannot calculate
     */
    public function getDaysUntil($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        if ($this->timestamp == null || $other_time->getTimestamp() == null) return false;
        $difference_in_seconds = $other_time->timestamp - $this->timestamp;
        return (int)($difference_in_seconds/(60*60*24));
    }
    
    /**
     * Get the earliest of two times
     * @param \Platform\Time $other_time
     * @return boolean|\Platform\Time The earliest time or false if cannot determine
     */
    public function getEarliest($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        if ($this->timestamp == null || $other_time->getTimestamp() == null) return false;
        return $this->isBefore($other_time) ? $this : $other_time;
    }

    /**
     * Get days in this month
     * @return int Days in month
     */
    public function getDaysInMonth() {
        return date('t', $this->timestamp);
    }
    
    /**
     * Get the first day in this month
     * @return \Platform\Mtime
     */
    public function getFirstDayInMonth() {
        return $this->addDays(-($this->getDay())+1);
    }
    
    /**
     * Get the first day in this week
     * @return \Platform\Time
     */
    public function getFirstDayInWeek() {
        return $this->addDays(-($this->getWeekday()-1));
    }
    
    /**
     * Get the last day in this month
     * @return \Platform\Time
     */
    public function getLastDayInMonth() {
        return $this->add($this->getDaysInMonth()-$this->getDay());
    }
    
    /**
     * Get the latest of two times
     * @param \Platform\Time $other_time
     * @return boolean|\Platform\Time The latest time or false if cannot determine
     */
    public function getLatest($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        if ($this->timestamp == null || $other_time->getTimestamp() == null) return false;
        return $this->isAfter($other_time) ? $this : $other_time;
    }
    
    /**
     * Get month
     * @return int
     */
    public function getMonth() {
        return date('n', $this->timestamp);
    }
    
    /**
     * Get the number of months until another time
     * @param \Platform\Time $other_time
     * @return boolean|int Number of months or false if cannot calculate
     */
    public function getMonthsUntil($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        if ($this->timestamp == null || $other_time->getTimestamp() == null) return false;
        $difference_in_months = $other_time->getYear()*12+$other_time->getMonth()-($this->getYear()*12+$this->getMonth());
        if ($difference_in_months > 0 && $other_time->getDay() < $this->getDay()) return $difference_in_months-1;
        if ($difference_in_months < 0 && $other_time->getDay() > $this->getDay()) return $difference_in_months+1;
        return $difference_in_months;
    }
    
    /**
     * Get the number of minutes until another time
     * @param \Platform\Time $other_time
     * @return boolean|int Number of minutes or false if cannot calculate
     */
    public function getMinutesUntil($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        if ($other_time->getTimestamp() === null || $this->getTimestamp() === null) return false;
        return floor(($other_time->getTimestamp()-$this->getTimestamp())/60);
    }
    
    /**
     * Get this time in a readable format
     * @param string $format Format to use
     * @return string
     */
    public function getReadable($format = 'd-m-Y H:i') {
        Errorhandler::checkParams($format, 'string');
        if ($this->timestamp !== null) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($this->timestamp);
            if (self::$time_zone_object) $datetime->setTimezone(self::$time_zone_object);
            return $datetime->format($format);
        }
        return '';
    }
    
    /**
     * Get time
     * @return string
     */
    public function getTime() {
        return date('H:i:s', $this->timestamp);
    }    

    /**
     * Get the internal timestamp of this time
     * @return int
     */
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    /**
     * Get week
     * @return int
     */
    public function getWeek() {
        return date('W', $this->timestamp);
    }
    
    /**
     * Get weekday (1-mon 2-tue ...)
     * @return int
     */
    public function getWeekday() {
        return date('N', $this->timestamp);
    }
    
    /**
     * Get year
     * @return int
     */
    public function getYear() {
        return date('Y', $this->timestamp);
    }
    
    /**
     * Get the number of years until another time
     * @param \Platform\Time $other_time
     * @return boolean|int Number of months or false if cannot calculate
     */
    public function getYearsUntil($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        return (int)($this->getMonthsUntil($other_time)/12.0);
    }
    
    /**
     * Check if this time is after another time
     * @param \Platform\Time $other_time
     * @return boolean
     */
    public function isAfter($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        if ($this->timestamp === null || $other_time->getTimestamp() === null) return false;
        return $this->timestamp > $other_time->getTimestamp();
    }
    
    /**
     * Check if this time is before another time
     * @param \Platform\Time $other_time
     * @return boolean
     */
    public function isBefore($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        if ($this->timestamp === null || $other_time->getTimestamp() === null) return false;
        return $this->timestamp < $other_time->getTimestamp();
    }
    
    /**
     * Check if this time is null ie. not set at all
     * @return boolean True if not set
     */
    public function isNull() {
        return $this->timestamp === null;
    }
    
    /**
     * Check if this date is the same date as another time
     * @param \Platform\Time $other_time
     * @return boolean
     */
    public function isSameDate($other_time) {
        Errorhandler::checkParams($other_time, '\\Platform\\Time');
        return $this->getDay() == $other_time->getDay() && $this->getMonth() == $other_time->getMonth() && $this->getYear() == $other_time->getYear();
    }
    
    /**
     * Check if this time is in the weekend
     * @return boolean
     */
    public function isWeekend() {
        $weekday = $this->getWeekday();
        return $weekday >= 6;
    }
    
    /**
     * Return a new time with the current time
     * @return \Platform\Time
     */
    public static function now() {
        return new Time('now');
    }
    
    /**
     * Set the timezone for displaying time
     * @param string $display_time_zone
     */
    public static function setDisplayTimeZone($display_time_zone) {
        self::$time_zone_object = new \DateTimeZone($display_time_zone);
    }
    
    /**
     * Return a new time with the current time at midnight today
     * @return \Platform\Time
     */
    public static function today() {
        return new Time('today');
    }
    
    /**
     * Get the total number of weeks in the given year
     * @param int $year Year
     * @return int Number of weeks in year 
     */
    public static function weeksInYear($year) {
        Errorhandler::checkParams($year, 'int');
        $ts = mktime(0,0,0,12,28,$year);
        return (int)date('W', $ts);
    }    
    
}