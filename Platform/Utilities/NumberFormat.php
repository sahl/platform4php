<?php
namespace Platform\Utilities;

class NumberFormat {
    
    const FORMAT_EU = 1; // 100.000,00
    const FORMAT_US = 2; // 100,000.00
    
    private static $format = self::FORMAT_EU;
    
    /**
     * Set the numeric format
     * @param int $format
     * @param bool $store_in_session If true, then store it in the session
     */
    public static function setFormat(int $format, bool $store_in_session = true) {
        if (! in_array($format, [1,2])) trigger_error('Invalid format specified: \''.$format.'\'!', E_USER_ERROR);
        static::$format = $format;
        if ($store_in_session) $_SESSION['platform']['number_format'] = $format;
        if (!\Platform\Page\Page::isPageStarted()) \Platform\Page\Page::addData('platform_number_format', $format);
    }
    
    /**
     * Set the format from a value previously stored in the session
     */
    public static function setFormatFromSession() {
        static::$format = $_SESSION['platform']['number_format'] ?: static::$format;
    }
    
    /**
     * Get the current numeric format
     * @return int
     */
    public static function getFormat() : int {
        return static::$format;
    }
    
    /**
     * Get the character for separating decimals
     * @return string
     */
    public static function getDecimalSeparator() : string {
        switch (static::$format) {
            case static::FORMAT_EU:
                return ',';
            case static::FORMAT_US:
                return '.';
            default:
                trigger_error('Unknown format', E_USER_ERROR);
        }
    }
    
    /**
     * Format a number according to the current numeric format
     * @param mixed $number The number to format
     * @param int $decimals The number of decimals to show (if any)
     * @param bool $separate_thousands Set to true if thousands should be marked.
     * @return string The formatted number as a string
     */
    public static function getFormattedNumber($number, int $decimals = 0, bool $separate_thousands = false) : string {
        if (!is_numeric($number)) return '';
        $decimal_separator = static::getDecimalSeparator();
        $thousand_separator = static::getThousandSeparator();
        return number_format($number, $decimals, $decimal_separator, $separate_thousands ? $thousand_separator : '');
    }

    /**
     * Return the number of decimals in a given unformatted number
     * @param mixed $number
     * @return int Number of decimals
     */
    public static function getNumberOfDecimals($number) : int {
        $decimal_position = strpos($number, '.');
        if ($decimal_position === false|| !is_numeric($number)) return 0;
        return strlen($number)-$decimal_position-1;
    }

    /**
     * Get the character for separating thousands
     * @return string
     */
    public static function getThousandSeparator() : string {
        switch (static::$format) {
            case static::FORMAT_EU:
                return '.';
            case static::FORMAT_US:
                return ',';
            default:
                trigger_error('Unknown format', E_USER_ERROR);
        }
    }
    
    /**
     * Converts a formatted string (as formatted by getFormattedNumber) to a float
     * @param string $value Formatted value
     * @return Value as float unless it is null or an empty string
     */
    public static function getUnformattedNumber(string $value) {
        if ($value === null) return null;
        if ($value === '') return '';
        return (float)str_replace(static::getDecimalSeparator(), '.', str_replace(static::getThousandSeparator(), '', $value));
    }
    
    /**
     * Check if this is a valid formatted value
     * @param string $value
     * @return type
     */
    public static function isValid(string $value) {
        return preg_match('/^(\\d{1,3}\\'.static::getThousandSeparator().'?)(\\d{3}\\'.static::getThousandSeparator().'?)*('.static::getDecimalSeparator().'\\d+)?$/', $value) != 0;
    }
}