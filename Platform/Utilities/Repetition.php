<?php
namespace Platform\Utilities;

class Repetition {
    
    const REPEAT_DAILY = 1;
    const REPEAT_WEEKLY = 2;
    const REPEAT_MONTHLY = 3;
    const REPEAT_YEARLY = 4;
    
    private $type = self::REPEAT_DAILY;
    
    private $interval = 1;
    
    private $metadata = [];
    
    /**
     * Construct a repetition
     * @param int $repeat_type Repetition type
     * @param int $repeat_interval Repetition interval
     * @param array $metadata Repetition metadata
     */
    public function __construct(int $repeat_type = 1, int $repeat_interval = 1, array $metadata = []) {
        if (! in_array($repeat_type,[1,2,3,4])) trigger_error('Invalid repetition type', E_USER_ERROR);
        if ($repeat_interval < 1 || $repeat_interval > 365) trigger_error('Invalid repeat interval');
        
        $this->type = $repeat_type;
        $this->interval = $repeat_interval;
        $this->metadata = $metadata;
    }
    
    /**
     * Construct a repetition from an array
     * @param array $repetition An array as retrieved from getAsArray
     * @return Repetition
     */
    public static function constructFromArray(array $repetition) : Repetition {
        return new Repetition($repetition['type'], $repetition['interval'], $repetition['metadata']);
    }
    
    /**
     * Get a human-readable description of this repetition
     * @return string
     */
    public function getDescription() : string {
        $types = [
            self::REPEAT_DAILY => Translation::translateForUser('day'),
            self::REPEAT_WEEKLY => Translation::translateForUser('week'),
            self::REPEAT_MONTHLY => Translation::translateForUser('month'),
            self::REPEAT_YEARLY => Translation::translateForUser('year')
        ];
        $result = Translation::translateForUser('Every');
        if ($this->interval > 1) $result .= ' '.$this->interval.'.';
        $result .= ' '.$types[$this->type];
        switch ($this->type) {
            case self::REPEAT_WEEKLY:
                $weekdays = Time::getWeekDaysArray();
                $result .= ' '.Translation::translateForUser('on').' ';
                $days = [];
                foreach ($this->metadata['weekdays'] as $day) {
                    $days[] = $weekdays[$day];
                }
                $result .= implode(', ', $days);
                break;
            case self::REPEAT_MONTHLY:
                $weekdays = Time::getWeekDaysArray();
                if ($this->metadata['monthday']) {
                    $result .= ' '.Translation::translateForUser('on the %1.', $this->metadata['monthday']);
                } elseif ($this->metadata['weekday']) {
                    $occurrence_array = [
                        -2 => Translation::translateForUser('second-last'),
                        -1 => Translation::translateForUser('last'),
                        1 => Translation::translateForUser('first'),
                        2 => Translation::translateForUser('second'),
                        3 => Translation::translateForUser('third'),
                        4 => Translation::translateForUser('fourth'),
                    ];
                    $result .= ' '.Translation::translateForUser('on the %1 %2', $occurrence_array[$this->metadata['occurrence']], $weekdays[$this->metadata['weekday']]);
                }
                break;
            case self::REPEAT_YEARLY:
                $months = Time::getMonthsArray();
                $result .= ' '.Translation::translateForUser('on %1 %2.', $months[$this->metadata['month']], $this->metadata['day']);
                break;
        }
        return $result;
    }
    
    /**
     * Get this repetition as an array
     * @return array
     */
    public function getAsArray() : array {
        return ['type' => $this->type, 'interval' => $this->interval, 'metadata' => $this->metadata];
    }
    
    /**
     * Check if a given date matches this repetition
     * @param Time $date_to_match The date to check
     * @param Time $start_date A start date for the repetition. If not given, todays date is used
     * @return bool True if the date matches the repetition
     */
    public function match(Time $date_to_match, Time $start_date = null) : bool {
        $date_to_match = $date_to_match->startOfDay();
        
        if ($start_date === null) $start_date = Time::today();
        else $start_date = $start_date->startOfDay();
        
        if ($date_to_match->isBefore($start_date)) return false;
        
        switch ($this->type) {
            case self::REPEAT_DAILY:
                // We match if there is the specific number of days between the start date and measure date
                return $start_date->getDaysUntil($date_to_match)%$this->interval == 0;
                
            case self::REPEAT_WEEKLY:
                // Check for weekday match
                if (is_array($this->metadata['weekdays']) && ! in_array($date_to_match->getWeekday(), $this->metadata['weekdays'])) return false;
                // Check for difference in week by rewinding to monday
                $start_date_monday = $start_date->addDays(1-$start_date->getWeekday());
                $difference_in_weeks = floor($start_date_monday->getDaysUntil($date_to_match)/7);
                return $difference_in_weeks%$this->interval == 0;
                
            case self::REPEAT_MONTHLY:
                // Check repetition
                $start_date_first_in_month = $start_date->addDays(1-$start_date->getDay());
                $difference_in_months = floor($start_date_first_in_month->getMonthsUntil($date_to_match));
                if ($difference_in_months%$this->interval > 0) return false;
                
                // Check specific months
                if (is_array($this->metadata['months']) && ! in_array($date_to_match->getMonth(), $this->metadata['months'])) return false;
                
                // Check a certain day in the month
                if ($this->metadata['monthday'] && $date_to_match->getDay() != $this->metadata['monthday']) return false;
                
                // Check for specific day type in month
                if ($this->metadata['weekday']) {
                    if ($date_to_match->getWeekday() != $this->metadata['weekday']) return false;
                    // Check for first, second, third...
                    if ($this->metadata['occurrence']) {
                        $outside_date = $date_to_match->addDays(-7*$this->metadata['occurrence']);
                        $inside_date = $outside_date->addDays($this->metadata['occurrence'] > 0 ? 7 : -7);
                        if ($outside_date->getMonth() == $date_to_match->getMonth() || $inside_date->getMonth() != $date_to_match->getMonth()) return false;
                    }
                }
                return true;
            
            case self::REPEAT_YEARLY:
                // Check repetition
                $start_date_first_in_year = new Time($start_date->getYear().'-01-01');
                $difference_in_years = floor($start_date_first_in_year->getYearsUntil($date_to_match));
                if ($difference_in_years%$this->interval > 0) return false;
                
                // Check for specific date
                if ($this->metadata['day'] && $this->metadata['day'] != $date_to_match->getDay() || $this->metadata['month'] && $this->metadata['month'] != $date_to_match->getMonth()) return false;
                return true;
        }
        return false;
    }
}