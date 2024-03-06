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
     * Format a number according to the current numeric format
     * @param mixed $number The number to format
     * @param int $decimals The number of decimals to show (if any)
     * @param bool $thousand_separator Set to true if decimal numbers should be separated.
     * @return string The formatted number as a string
     */
    public static function getFormattedNumber($number, int $decimals = 0, bool $thousand_separator = false) : string {
        if (!is_numeric($number)) return '';
        switch (static::$format) {
            case static::FORMAT_EU:
                return number_format($number, $decimals, ',', $thousand_separator ? '.' : '');
            case static::FORMAT_US:
                return number_format($number, $decimals, '.', $thousand_separator ? ',' : '');
        }
    }
}