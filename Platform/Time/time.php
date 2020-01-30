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
     * @param mixed $time Either another time object to copy, a time string or "now" for current time.
     */
    public function __construct($time = null) {
        if ($time instanceof Time) $this->timestamp = $time->getTimestamp();
        elseif ($time == 'now') $this->timestamp = time();
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
     * @param Time $ts Other timestamp
     * @return boolean
     */
    public function equalTo($ts) {
        if (! $ts instanceof Time) return false;
        return $this->timestamp == $ts->getTimestamp();
    }
    
    /**
     * Get the number of days in the given month
     * @param int $month Month
     * @param int $year Year
     * @return int Number of days
     */
    public static function daysInMonth($month, $year) {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        return date('t', $timestamp);
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
     * @param \Platform\Time $time
     * @return boolean|int Number of days or false if cannot calculate
     */
    public function getDaysUntil($time) {
        if (! $time instanceof Time) return false;
        if ($this->timestamp == null || $time->getTimestamp() == null) return false;
        $difference_in_seconds = $time->timestamp - $this->timestamp;
        if ($difference_in_seconds < 0) return (int)(($res-12*60*60)/(60*60*24));
        return (int)(($res+12*60*60)/(60*60*24));
    }
    
    /**
     * Get the earliest of two times
     * @param \Platform\Time $time
     * @return boolean|\Platform\Time The earliest time or false if cannot determine
     */
    public function getEarliest($time) {
        if (! $time instanceof Time) return false;
        if ($this->timestamp == null || $time->getTimestamp() == null) return false;
        return $this->isBefore($time) ? $this : $time;
    }

    /**
     * Get the latest of two times
     * @param \Platform\Time $time
     * @return boolean|\Platform\Time The latest time or false if cannot determine
     */
    public function getLatest($time) {
        if (! $time instanceof Time) return false;
        if ($this->timestamp == null || $time->getTimestamp() == null) return false;
        return $this->isAfter($time) ? $this : $time;
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
     * @param \Platform\Time $time
     * @return boolean|int Number of months or false if cannot calculate
     */
    public function getMonthsUntil($time) {
        if (! $time instanceof Time) return false;
        if ($this->timestamp == null || $time->getTimestamp() == null) return false;
        $difference_in_months = $time->getYear()*12+$time->getMonth()-($this->getYear()*12+$this->getMonth());
        if ($difference_in_months > 0 && $time->getDay() < $this->getDay()) return $difference_in_months-1;
        if ($difference_in_months < 0 && $time->getDay() > $this->getDay()) return $difference_in_months+1;
        return $difference_in_months;
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
     * @param \Platform\Time $time
     * @return boolean|int Number of months or false if cannot calculate
     */
    public function getYearsUntil($time) {
        return (int)($this->getMonthsUntil($time)/12.0);
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
     * Get the number of minutes until another time
     * @param \Platform\Time $time
     * @return boolean|int Number of minutes or false if cannot calculate
     */
    public function getMinutesUntil($other_timestamp) {
        $other_timestamp = new Time($other_timestamp);
        if ($other_timestamp->getTimestamp() === null || $this->getTimestamp() === null) return false;
        return floor(($other_timestamp->getTimestamp()-$this->getTimestamp())/60);
    }
    
    /**
     * Get this time in a readable format
     * @param string $format Format to use
     * @return string
     */
    public function getReadable($format = 'd-m-Y H:i') {
        if ($this->timestamp !== null) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($this->timestamp);
            if (self::$time_zone_object) $datetime->setTimezone(self::$time_zone_object);
            return $datetime->format($format);
        }
        return '';
    }
    
    /**
     * Get the internal timestamp of this time
     * @return int
     */
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    /**
     * Get this time as a string
     * @param string $format Format to use
     * @return string
     */
    public function getTime($format = 'Y-m-d H:i:s') {
        return $this->timestamp !== null ? date($format, $this->timestamp) : null;
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
     * Check if this time is after another time
     * @param \Platform\Time $othertime
     * @return boolean
     */
    public function isAfter($othertime) {
        $othertime = new Time($othertime);
        return $this->timestamp > $othertime->getTimestamp();
    }
    
    /**
     * Check if this time is before another time
     * @param \Platform\Time $othertime
     * @return boolean
     */
    public function isBefore($othertime) {
        $othertime = new Time($othertime);
        return $this->timestamp < $othertime->getTimestamp();
    }
    
    /**
     * Check if this date is the same date as another time
     * @param \Platform\Time $time
     * @return boolean
     */
    public function isSameDate($time) {
        return $this->getDay() == $time->getDay() && $this->getMonth() == $time->getMonth() && $this->getYear() == $time->getYear();
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
     * Get the total number of weeks in the given year
     * @param int $year Year
     * @return int Number of weeks in year 
     */
    public static function weeksInYear($year) {
        $ts = mktime(0,0,0,12,28,$year);
        return (int)date('W', $ts);
    }    
    
}