<?php
namespace Platform\Utilities;
/**
 * Class for describing a period of time ie. the duration between two Time objects
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=timeperiod_class
 */

class TimePeriod {
    
    private $start_time = null;
    private $end_time = null;
    
    public function __construct(Time $start_time = null, Time $end_time = null) {
        if ($start_time) $this->setStartTime($start_time);
        if ($end_time) $this->setEndTime($end_time);
    }

    /**
     * Cut this period against another period
     * @param TimePeriod $other_period The period to cut against
     * @return array The resulting period(s). There can be 0, 1 or 2
     */
    public function getCut(TimePeriod $other_period) : array {
        // Check if valid
        if (! $this->isSet() || ! $other_period->isSet()) return [];
        // If no overlap is detected, we don't cut
        if (! $this->getOverlap($other_period)->isSet()) return [$this];
        // If the period is consumed, nothing is returned
        if ($other_period->getStartTime()->isBeforeEqual($this->getStartTime()) && $other_period->getEndTime()->isAfterEqual($this->getEndTime())) return [];
        // With partial overlap, there can be up to two results
        $result = [];
        if ($this->getStartTime()->isBefore($other_period->getStartTime())) $result[] = new TimePeriod($this->getStartTime(), $other_period->getStartTime());
        if ($this->getEndTime()->isAfter($other_period->getEndTime())) $result[] = new TimePeriod($other_period->getEndTime(), $this->getEndTime());
        return $result;
    }
    
    
    public function getDurationInMinutes() {
        if ($this->start_time === null || $this->end_time === null) return false;
        return $this->start_time->getMinutesUntil($this->end_time);
    }
    
    public function getEndTime() : Time {
        return $this->end_time;
    }

    public function getStartTime() : Time {
        return $this->start_time;
    }
    
    /**
     * Get overlap between this period and another period
     * @param TimePeriod $other_period The period to check against
     * @return TimePeriod A period which contains the overlap or is empty if there is no overlap
     */
    public function getOverlap(TimePeriod $other_period) : TimePeriod {
        // Check if no overlap
        if (! $this->isSet() || 
                ! $other_period->isSet() || 
                $this->getEndTime()->isBeforeEqual($other_period->getStartTime()) || 
                $other_period->getEndTime()->isBeforeEqual($this->getStartTime())) return new TimePeriod();
        // Return overlap
        return new TimePeriod($this->getStartTime()->getLatest($other_period->getStartTime()), $this->getEndTime()->getEarliest($other_period->getEndTime()));
    }
    
    public function isSet() : bool {
        return ($this->start_time instanceof Time && $this->end_time instanceof Time);
    }
    
    public function setEndTime(Time $end_time) {
        if ($this->start_time !== null && $end_time->isBefore($this->start_time)) trigger_error('End time cannot be before start time', E_USER_ERROR);
        $this->end_time = $end_time;
    }

    public function setStartTime(Time $start_time) {
        if ($this->end_time !== null && $start_time->isAfter($this->end_time)) trigger_error('Start time cannot be after end time', E_USER_ERROR);
        $this->start_time = $start_time;
    }
    
}
