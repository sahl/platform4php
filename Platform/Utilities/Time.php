<?php
namespace Platform\Utilities;

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
     * Default visual date format
     * @var string
     */
    private static $date_format = 'Y-m-d';
    
    /**
     * Default visual time format
     * @var string
     */
    private static $time_format = 'h:i';
    
    /**
     * Construct a new Time object
     * @param mixed $time Either another time object to copy, a time string in UTC, a time stamp, "now" for current time "today" for today at midnight or "end_of_today" for today right before tomorrow.
     */
    public function __construct($time = null) {
        Errorhandler::checkParams($time, array('string', '\\Platform\\Utilities\\Time', 'integer'));
        if ($time instanceof Time) $this->timestamp = $time->getTimestamp();
        elseif ($time == 'now') $this->timestamp = time();
        elseif ($time == 'today') $this->timestamp = strtotime(self::now()->get('Y-m-d'));
        elseif ($time == 'end_of_today') $this->timestamp = strtotime(self::now()->get('Y-m-d 23:59:59'));
        elseif (is_numeric($time)) $this->timestamp = (int)$time;
        elseif ($time) {
            // Ensure we are UTC when setting
            $stored_time_zone = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $this->timestamp = strtotime($time);
            date_default_timezone_set($stored_time_zone);
        }
    }
    
    /**
     * Add to the time part of the datestamp
     * @param int $seconds Seconds to add
     * @param int $minutes Minutes to add
     * @param int $hours Hours to add
     * @return $this
     */
    public function add(int $seconds, int $minutes = 0, int $hours = 0) : Time {
        $result = new Time($this->getTimestamp()+ $seconds + $minutes*60 + $hours*3600);
        return $result;
    }
    
    /**
     * Add to the date part of the timestamp
     * @param int $days Days to add
     * @param int $months Months to add
     * @param int $years Years to add
     */
    public function addDays(int $days, int $months = 0, int $years = 0) : Time {
        $new_timestamp = $this->getTimestamp();
        if ($months || $years) {
            // We handle this a little special
            $newyear = date('Y', $new_timestamp)+$years;
            $newmonth = date('n', $new_timestamp)+$months;
            $currentday = date('j', $new_timestamp);
            if ($currentday > self::daysInMonth($newmonth, $newyear)) $currentday = self::daysInMonth ($newmonth, $newyear);
            $new_timestamp = mktime(date('H', $new_timestamp), date('i', $new_timestamp), date('s', $new_timestamp), $newmonth, $currentday, $newyear);
        }
        $new_timestamp += 24*60*60*$days;
        return new Time($new_timestamp);
    }
    
    /**
     * Get the number of days in the given month
     * @param int $month Month
     * @param int $year Year
     * @return int Number of days
     */
    public static function daysInMonth(int $month, int $year) : int {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        return date('t', $timestamp);
    }
    
    /**
     * Get the end of day of this time
     * @return Time
     */
    public function endOfDay() : Time {
        $newtimestamp = strtotime($this->get('Y-m-d 23:59:59'));
        return new Time($newtimestamp);
    }
    
    /**
     * Get this time as a string
     * @param string $format Format to use
     * @return string
     */
    public function get(string $format = 'Y-m-d H:i:s') {
        return $this->timestamp !== null ? date($format, $this->timestamp) : null;
    }
    
    /**
     * Get a sensible array of possible time zones
     * @return array
     */
    public static function getTimeZones() : array {
        return static::$timezone_array;
    }

    /**
     * Get date on form YYYY-MM-DD
     * @return string
     */
    public function getDate() : string {
        return date('Y-m-d', $this->timestamp);
    }
    
    /**
     * Get the date format from session
     * @return string Date format or false if none found
     */
    public static function getDateFormatFromSession() {
        return $_SESSION['platform']['date_format'] ?: false;
    }
    
    /**
     * Get day
     * @return int
     */
    public function getDay() : int {
        return date('j', $this->timestamp);
    }
    
    /**
     * Get the number of days until another time. This only considers the date-part of the Time
     * @param \Platform\Utilities\Time $other_time
     * @return bool|int Number of days or false if cannot calculate
     */
    public function getDaysUntil(Time $other_time) {
        if ($this->getTimestamp() == null || $other_time->getTimestamp() == null) return false;
        $difference_in_seconds = $other_time->startOfDay()->getTimestamp() - $this->startOfDay()->getTimestamp();
        return round($difference_in_seconds/(60*60*24));
    }
    
    /**
     * Get the earliest of two times
     * @param \Platform\Utilities\Time $other_time
     * @return bool|\Platform\Utilities\Time The earliest time or false if cannot determine
     */
    public function getEarliest(Time $other_time) {
        if ($this->timestamp == null || $other_time->getTimestamp() == null) return false;
        return new Time($this->isBefore($other_time) ? $this : $other_time);
    }

    /**
     * Get days in this month
     * @return int Days in month
     */
    public function getDaysInMonth() : int {
        return date('t', $this->timestamp);
    }

    /**
     * Get the display time zone from session
     * @return string|bool The time zone or false if no time zone was stored.
     */
    public static function getDisplayTimeZoneFromSession() {
        return $_SESSION['platform']['display_timezone'] ?: false;
    }
    
    /**
     * Get the first day in this month
     * @return \Platform\Mtime
     */
    public function getFirstDayInMonth() : Time {
        return $this->addDays(-($this->getDay())+1);
    }
    
    /**
     * Get the first day in this week
     * @return \Platform\Utilities\Time
     */
    public function getFirstDayInWeek() : Time {
        return $this->addDays(-($this->getWeekday()-1));
    }
    
    public function getHour() : int {
        return (int)date('G', $this->timestamp);
    }
    
    /**
     * Get the last day in this month
     * @return \Platform\Utilities\Time
     */
    public function getLastDayInMonth() : Time {
        return $this->add($this->getDaysInMonth()-$this->getDay());
    }
    
    /**
     * Get the latest of two times
     * @param \Platform\Utilities\Time $other_time
     * @return bool|\Platform\Utilities\Time The latest time or false if cannot determine
     */
    public function getLatest(Time $other_time) {
        if ($this->timestamp == null || $other_time->getTimestamp() == null) return false;
        return $this->isAfter($other_time) ? $this : $other_time;
    }
    
    public function getMinute() : int {
        return (int)date('i', $this->timestamp);
    }
    
    /**
     * Get month
     * @return int
     */
    public function getMonth() : int {
        return date('n', $this->timestamp);
    }
    
    /**
     * Get an array of all month names
     * @return array
     */
    public static function getMonthsArray() : array {
        return 
            [
                1 => Translation::translateForUser('January'),
                2 => Translation::translateForUser('February'),
                3 => Translation::translateForUser('March'),
                4 => Translation::translateForUser('April'),
                5 => Translation::translateForUser('May'),
                6 => Translation::translateForUser('June'),
                7 => Translation::translateForUser('July'),
                8 => Translation::translateForUser('August'),
                9 => Translation::translateForUser('September'),
                10=> Translation::translateForUser('October'),
                11=> Translation::translateForUser('November'),
                12=> Translation::translateForUser('December'),
            ];
    }
    
    
    /**
     * Get the number of months until another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool|int Number of months or false if cannot calculate
     */
    public function getMonthsUntil(Time $other_time) {
        if ($this->timestamp == null || $other_time->getTimestamp() == null) return false;
        $difference_in_months = $other_time->getYear()*12+$other_time->getMonth()-($this->getYear()*12+$this->getMonth());
        if ($difference_in_months > 0 && $other_time->getDay() < $this->getDay()) return $difference_in_months-1;
        if ($difference_in_months < 0 && $other_time->getDay() > $this->getDay()) return $difference_in_months+1;
        return $difference_in_months;
    }
    
    /**
     * Get the number of minutes until another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool|int Number of minutes or false if cannot calculate
     */
    public function getMinutesUntil(Time $other_time) {
        if ($other_time->getTimestamp() === null || $this->getTimestamp() === null) return false;
        return floor(($other_time->getTimestamp()-$this->getTimestamp())/60);
    }
    
    /**
     * Get this time in a readable format
     * @param string $format Format to use
     * @return string
     */
    public function getReadable(string $format = '') : string {
        if (! $format) $format = static::$date_format.' '.static::$time_format;
        if ($this->timestamp !== null) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($this->timestamp);
            if (self::$time_zone_object) $datetime->setTimezone(self::$time_zone_object);
            return $datetime->format($format);
        }
        return '';
    }
    
    /**
     * Get the date part of this time in a readable format
     * @param string $format Format to use
     * @return string
     */
    public function getReadableDate(string $format = '') : string {
        if (! $format) $format = static::$date_format ?: 'Y-m-d';
        return $this->getReadable($format);
    }
    
    /**
     * Get the time part of this time in a readable format
     * @param string $format Format to use
     * @return string
     */
    public function getReadableTime(string $format = '') : string {
        if (! $format) $format = static::$time_format ?: 'h:i';
        return $this->getReadable($format);
    }
    
    public function getSecond() : int {
        return (int)date('s', $this->timestamp);
    }

    /**
     * Get time on form HH:MM:SS
     * @return string
     */
    public function getTime() : string {
        return date('H:i:s', $this->timestamp);
    }    
    
    /**
     * Get the time format from session
     * @return string Time format or false if none found
     */
    public static function getTimeFormatFromSession() {
        return $_SESSION['platform']['time_format'] ?: false;
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
    public function getWeek() : int {
        return (int)date('W', $this->timestamp);
    }
    
    /**
     * Get weekday (1-mon 2-tue ...)
     * @return int
     */
    public function getWeekday() : int {
        return (int) date('N', $this->timestamp);
    }
    
    /**
     * Get an array with all weekday names hashed by their index
     * @return array
     */
    public static function getWeekDaysArray() : array {
        return 
            [
                1 => Translation::translateForUser('Monday'),
                2 => Translation::translateForUser('Tuesday'),
                3 => Translation::translateForUser('Wednesday'),
                4 => Translation::translateForUser('Thursday'),
                5 => Translation::translateForUser('Friday'),
                6 => Translation::translateForUser('Saturday'),
                7 => Translation::translateForUser('Sunday'),
            ];
    }
    
    /**
     * Get year
     * @return int
     */
    public function getYear() : int {
        return (int) date('Y', $this->timestamp);
    }
    
    /**
     * Get the number of years until another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool|int Number of months or false if cannot calculate
     */
    public function getYearsUntil(Time $other_time) {
        if ($this->timestamp === null || $other_time->getTimestamp() === null) return false;
        return (int)($this->getMonthsUntil($other_time)/12.0);
    }
    
    /**
     * Check if this time is after another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool
     */
    public function isAfter(Time $other_time) {
        if ($this->timestamp === null || $other_time->getTimestamp() === null) return false;
        return $this->timestamp > $other_time->getTimestamp();
    }
    
    /**
     * Check if this time is after or equal to another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool
     */
    public function isAfterEqual(Time $other_time) {
        if ($this->timestamp === null || $other_time->getTimestamp() === null) return false;
        return $this->timestamp >= $other_time->getTimestamp();
    }
    
    /**
     * Check if this time is before another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool
     */
    public function isBefore(Time $other_time) {
        if ($this->timestamp === null || $other_time->getTimestamp() === null) return false;
        return $this->timestamp < $other_time->getTimestamp();
    }
    
    /**
     * Check if this time is before or equal to another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool
     */
    public function isBeforeEqual(Time $other_time) {
        if ($this->timestamp === null || $other_time->getTimestamp() === null) return false;
        return $this->timestamp <= $other_time->getTimestamp();
    }
    
    /**
     * Check if this timestamp is equal to another timestamp
     * @param Time $other_time Other time
     * @return bool
     */
    public function isEqualTo(Time $other_time) {
        return $this->timestamp == $other_time->getTimestamp();
    }    
    
    /**
     * Check if this time is null ie. not set at all
     * @return bool True if not set
     */
    public function isNull() : bool {
        return $this->timestamp === null;
    }
    
    /**
     * Check if this date is the same date as another time
     * @param \Platform\Utilities\Time $other_time
     * @return bool
     */
    public function isSameDate(Time $other_time) {
        return $this->getDay() == $other_time->getDay() && $this->getMonth() == $other_time->getMonth() && $this->getYear() == $other_time->getYear();
    }
    
    /**
     * Check if this time is in the weekend
     * @return bool
     */
    public function isWeekend() : bool {
        $weekday = $this->getWeekday();
        return $weekday >= 6;
    }
    
    /**
     * Return a new time with the current time
     * @return \Platform\Utilities\Time
     */
    public static function now() {
        return new Time('now');
    }
    
    /**
     * Set the date format when rendering readable dates
     * @param string $date_format Format as a value compatible with date()
     * @param bool $store_in_session Set to true, to store in session
     */
    public static function setDateFormat(string $date_format, bool $store_in_session = false) {
        static::$date_format = $date_format;
        if ($store_in_session) $_SESSION['platform']['date_format'] = $date_format;
    }
    
    /**
     * Set the date and time format from session, if available
     */
    public static function setDateAndTimeFormatFromSession() {
        $date_format = static::getDateFormatFromSession();
        if ($date_format) static::setDateFormat($date_format);
        $time_format = static::getTimeFormatFromSession();
        if ($time_format) static::setTimeFormat($time_format);
    }
    
    public function setDay(int $day) {
        return new Time(strtotime($this->get('Y-m-'.str_pad($day,2,'0', STR_PAD_LEFT).' H:i:s')));
    }

    /**
     * Set the timezone for displaying time
     * @param string $display_timezone
     * @param bool $store_in_session If true then store the timezone in the session
     */
    public static function setDisplayTimeZone(string $display_timezone, bool $store_in_session = false) {
        self::$time_zone_object = new \DateTimeZone($display_timezone);
        if ($store_in_session) $_SESSION['platform']['display_timezone'] = $display_timezone;
    }

    /**
     * Set the display time zone from a value stored in the session
     */
    public static function setDisplayTimeZoneFromSession() {
        $display_timezone = static::getDisplayTimeZoneFromSession();
        if (! $display_timezone) return;
        static::setDisplayTimeZone($display_timezone);
    }

    public function setMonth(int $month) {
        return new Time(strtotime($this->get('Y-'.str_pad($month,2,'0', STR_PAD_LEFT).'-d H:i:s')));
    }
    
    /**
     * Set the time format when rendering readable times
     * @param string $time_format Time format
     * @param bool $store_in_session Set to true, to store in session
     */
    public function setTimeFormat(string $time_format, bool $store_in_session = false) {
        static::$time_format = $time_format;
        if ($store_in_session) $_SESSION['platform']['time_format'] = $time_format;
    }
    
    public function setYear(int $year) {
        return new Time(strtotime($this->get($year.'-m-d H:i:s')));
    }
    
    /**
     * Get the start of day of this time
     * @return Time
     */
    public function startOfDay() : Time {
        return new Time(strtotime($this->get('Y-m-d 00:00:00')));
    }    
    
    /**
     * Return a new time with the current time at midnight today
     * @return \Platform\Utilities\Time
     */
    public static function today() : Time {
        return new Time('today');
    }
    
    /**
     * Return a new time with the current time at midnight today
     * @return \Platform\Utilities\Time
     */
    public static function endoftoday() : Time {
        return new Time('end_of_today');
    }    
    
    /**
     * Get the total number of weeks in the given year
     * @param int $year Year
     * @return int Number of weeks in year 
     */
    public static function weeksInYear(int $year) : int {
        $ts = mktime(0,0,0,12,28,$year);
        return (int)date('W', $ts);
    }
    
    private static $timezone_array = [
        'Pacific/Midway' => '(GMT-11:00) Midway Island, Samoa',
        'America/Adak' => '(GMT-10:00) Hawaii-Aleutian',
        'Etc/GMT+10' => '(GMT-10:00) Hawaii',
        'Pacific/Marquesas' => '(GMT-09:30) Marquesas Islands',
        'Pacific/Gambier' => '(GMT-09:00) Gambier Islands',
        'America/Anchorage' => '(GMT-09:00) Alaska',
        'America/Ensenada' => '(GMT-08:00) Tijuana, Baja California',
        'Etc/GMT+8' => '(GMT-08:00) Pitcairn Islands',
        'America/Los_Angeles' => '(GMT-08:00) Pacific Time (US & Canada)',
        'America/Denver' => '(GMT-07:00) Mountain Time (US & Canada)',
        'America/Chihuahua' => '(GMT-07:00) Chihuahua, La Paz, Mazatlan',
        'America/Dawson_Creek' => '(GMT-07:00) Arizona',
        'America/Belize' => '(GMT-06:00) Saskatchewan, Central America',
        'America/Cancun' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey',
        'Chile/EasterIsland' => '(GMT-06:00) Easter Island',
        'America/Chicago' => '(GMT-06:00) Central Time (US & Canada)',
        'America/New_York' => '(GMT-05:00) Eastern Time (US & Canada)',
        'America/Havana' => '(GMT-05:00) Cuba',
        'America/Bogota' => '(GMT-05:00) Bogota, Lima, Quito, Rio Branco',
        'America/Caracas' => '(GMT-04:30) Caracas',
        'America/Santiago' => '(GMT-04:00) Santiago',
        'America/La_Paz' => '(GMT-04:00) La Paz',
        'Atlantic/Stanley' => '(GMT-04:00) Faukland Islands',
        'America/Campo_Grande' => '(GMT-04:00) Brazil',
        'America/Goose_Bay' => '(GMT-04:00) Atlantic Time (Goose Bay)',
        'America/Glace_Bay' => '(GMT-04:00) Atlantic Time (Canada)',
        'America/St_Johns' => '(GMT-03:30) Newfoundland',
        'America/Araguaina' => '(GMT-03:00) UTC-3',
        'America/Montevideo' => '(GMT-03:00) Montevideo',
        'America/Miquelon' => '(GMT-03:00) Miquelon, St. Pierre',
        'America/Godthab' => '(GMT-03:00) Greenland',
        'America/Argentina/Buenos_Aires' => '(GMT-03:00) Buenos Aires',
        'America/Sao_Paulo' => '(GMT-03:00) Brasilia',
        'America/Noronha' => '(GMT-02:00) Mid-Atlantic',
        'Atlantic/Cape_Verde' => '(GMT-01:00) Cape Verde Is.',
        'Atlantic/Azores' => '(GMT-01:00) Azores',
        'Europe/Belfast' => '(GMT) Greenwich Mean Time : Belfast',
        'Europe/Dublin' => '(GMT) Greenwich Mean Time : Dublin',
        'Europe/Lisbon' => '(GMT) Greenwich Mean Time : Lisbon',
        'Europe/London' => '(GMT) Greenwich Mean Time : London',
        'Africa/Abidjan' => '(GMT) Monrovia, Reykjavik',
        'Europe/Amsterdam' => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
        'Europe/Belgrade' => '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
        'Europe/Brussels' => '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris',
        'Africa/Algiers' => '(GMT+01:00) West Central Africa',
        'Africa/Windhoek' => '(GMT+01:00) Windhoek',
        'Asia/Beirut' => '(GMT+02:00) Beirut',
        'Africa/Cairo' => '(GMT+02:00) Cairo',
        'Asia/Gaza' => '(GMT+02:00) Gaza',
        'Africa/Blantyre' => '(GMT+02:00) Harare, Pretoria',
        'Asia/Jerusalem' => '(GMT+02:00) Jerusalem',
        'Europe/Minsk' => '(GMT+02:00) Minsk',
        'Asia/Damascus' => '(GMT+02:00) Syria',
        'Europe/Moscow' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd',
        'Africa/Addis_Ababa' => '(GMT+03:00) Nairobi',
        'Asia/Tehran' => '(GMT+03:30) Tehran',
        'Asia/Dubai' => '(GMT+04:00) Abu Dhabi, Muscat',
        'Asia/Yerevan' => '(GMT+04:00) Yerevan',
        'Asia/Kabul' => '(GMT+04:30) Kabul',
        'Asia/Yekaterinburg' => '(GMT+05:00) Ekaterinburg',
        'Asia/Tashkent' => '(GMT+05:00) Tashkent',
        'Asia/Kolkata' => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi',
        'Asia/Katmandu' => '(GMT+05:45) Kathmandu',
        'Asia/Dhaka' => '(GMT+06:00) Astana, Dhaka',
        'Asia/Novosibirsk' => '(GMT+06:00) Novosibirsk',
        'Asia/Rangoon' => '(GMT+06:30) Yangon (Rangoon)',
        'Asia/Bangkok' => '(GMT+07:00) Bangkok, Hanoi, Jakarta',
        'Asia/Krasnoyarsk' => '(GMT+07:00) Krasnoyarsk',
        'Asia/Hong_Kong' => '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi',
        'Asia/Irkutsk' => '(GMT+08:00) Irkutsk, Ulaan Bataar',
        'Australia/Perth' => '(GMT+08:00) Perth',
        'Australia/Eucla' => '(GMT+08:45) Eucla',
        'Asia/Tokyo' => '(GMT+09:00) Osaka, Sapporo, Tokyo',
        'Asia/Seoul' => '(GMT+09:00) Seoul',
        'Asia/Yakutsk' => '(GMT+09:00) Yakutsk',
        'Australia/Adelaide' => '(GMT+09:30) Adelaide',
        'Australia/Darwin' => '(GMT+09:30) Darwin',
        'Australia/Brisbane' => '(GMT+10:00) Brisbane',
        'Australia/Hobart' => '(GMT+10:00) Hobart',
        'Asia/Vladivostok' => '(GMT+10:00) Vladivostok',
        'Australia/Lord_Howe' => '(GMT+10:30) Lord Howe Island',
        'Etc/GMT-11' => '(GMT+11:00) Solomon Is., New Caledonia',
        'Asia/Magadan' => '(GMT+11:00) Magadan',
        'Pacific/Norfolk' => '(GMT+11:30) Norfolk Island',
        'Asia/Anadyr' => '(GMT+12:00) Anadyr, Kamchatka',
        'Pacific/Auckland' => '(GMT+12:00) Auckland, Wellington',
        'Etc/GMT-12' => '(GMT+12:00) Fiji, Kamchatka, Marshall Is.',
        'Pacific/Chatham' => '(GMT+12:45) Chatham Islands',
        'Pacific/Tongatapu' => '(GMT+13:00) Nuku\'alofa',
        'Pacific/Kiritimati' => '(GMT+14:00) Kiritimati'
    ];
}