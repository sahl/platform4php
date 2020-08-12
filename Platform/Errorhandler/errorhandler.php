<?php
namespace Platform;

class Errorhandler {
    /**
     * Used for setting a measure time limit.
     * @var mixed
     */
    private static $measure_time_limit = false;
    
    /**
     * Used for setting a measure start point
     * @var mixed
     */
    private static $measure_time_start = false;

    /**
     * Check type of parameters. Pass sets of variables and type keywords.
     */
    public static function checkParams() {
        $number_of_parameters = func_num_args();
        if ($number_of_parameters % 2 == 1) trigger_error('Invalid number of parameters to paramCheck', E_USER_ERROR);
        $parameters = func_get_args();
        for ($i = 0; $i < $number_of_parameters; $i+=2) {
            $value = $parameters[$i];
            $types = $parameters[$i+1];
            if (! is_array($types)) $types = array($types);
            $checked = array();
            foreach ($types as $type) {
                switch ($type) {
                    case 'boolean':
                        if (is_bool($value)) continue 3;
                        $checked[] = 'boolean';
                        break;
                    case 'integer':
                    case 'int':
                        if ($value === null) continue 3;
                        if (is_numeric($value) && strpos($value,'.') === false) continue 3;
                        $checked[] = 'integer';
                        break;
                    case 'float':
                        if ($value === null) continue 3;
                        if (is_numeric($value)) continue 3;
                        $checked[] = 'float';
                        break;
                    case 'string':
                        if ($value === null) continue 3;
                        if (is_string($value)) continue 3;
                        $checked[] = 'string';
                        break;
                    case 'resource':
                        if (is_resource($value)) continue 3;
                        $checked[] = 'resource';
                        break;
                    case 'object':
                        if (is_object($value)) continue 3;
                        $checked[] = 'object';
                        break;
                    case 'array':
                        if (is_array($value)) continue 3;
                        $checked[] = 'array';
                        break;
                    default:
                        if ($value === null || is_a($value, $type)) continue 3;
                        $checked[] = 'object '.$type;
                        break;
                }
            }
            $functionname = self::getCallingFunction(1);
            $parentfunction = self::getCallingFunction(2);
            
            if (count($checked) > 1) trigger_error('Parameter '.($i/2+1).' expected to be one of these types: '.implode(', ',$checked).' calling '.$functionname.' from '.$parentfunction.'. Found: '. gettype($value), E_USER_ERROR);
            else trigger_error('Parameter '.($i/2+1).' expected to be: '.current($checked).' calling '.$functionname.' from '.$parentfunction.'. Found: '. gettype($value), E_USER_ERROR);
        }
    }

    /**
     * Return the calling function on the form script.php:lineno
     * @param int $level Number of levels to go up in the call stack
     * @return string Called function
     */
    public static function getCallingFunction($level = 1) {
        $backtrace = debug_backtrace();
        if (count($backtrace) < $level)
            $level = count($backtrace) - 1;
        return $backtrace[$level]['file'] . ':' . $backtrace[$level]['line'];
    }

    /**
     * Return the full call stack as an array on the form script.php:lineno
     * @return array
     */
    public static function getFullCallStack() {
        $result = array();
        foreach (debug_backtrace() as $t) {
            $file = stristr($t['file'], $_SERVER['DOCUMENT_ROOT']) !== false ? substr($t['file'], strlen($_SERVER['DOCUMENT_ROOT'])) : $t['file'];
            $result[] = $file . ':' . $t['line'];
        }
        return $result;
    }
    
    public static function getMeasures() {
        return self::$measures;
    }
    
    private static $measures = array();
    
    public static function measure($text, $operation_count = false) {
        self::$measures[] = array('timestamp' => microtime(true), 'text' => $text, 'operation_count' => $operation_count);
        // Check if the measure limit is set and if it is exceeded
        if (self::$measure_time_limit !== false && self::$measure_time_start + self::$measure_time_limit < time()) {
            self::renderMeasures();
            exit;
        }
    }

    /**
     * Error handler
     * @param int $error_number Error number 
     * @param string $message Error text
     * @param string $file Error file
     * @param int $line Error line number
     * @return boolean
     */
    public static function handler($error_number, $message, $file, $line) {
        switch ($error_number) {
            case E_ERROR:
            case E_USER_ERROR:
                echo '<hr>';
                echo '<h3>Error occured</h3>';
                echo '<table><tr><th>Error:</th><td>' . $error_number . '</td></tr><tr><th>Error text:</th><td>' . $message . '</td></tr></table>';
                echo '<b>Stack dump</b>';
                echo '<table>';
                foreach (debug_backtrace() as $backtrace) {
                    if ($backtrace['file']) {
                        $display_arguments = array();
                        foreach ($backtrace['args'] as $argument) {
                            $display_arguments[] = print_r($argument, true);
                        }
                        echo '<tr><td>' . $backtrace['file'] . ':' . $backtrace['line'] . '</td><td>' . $backtrace['function'] . ' (' . implode(',', $display_arguments) . ')</td></tr>';
                    }
                }
                echo '</table>';
                if ($error_number == E_WARNING)
                    return true;
                die('Exit');
            default:
                return false;
        }
    }
    
    /**
     * Debug function to check for recursion. It will fail if recursion exceeds 
     * the given limit
     * @param int $level Recursion limit.
     */
    public static function recursionTest($level = 20) {
        if (count(debug_backtrace()) > $level) {
            trigger_error('Recursion exceeded (level '.$level.')', E_USER_ERROR);
        }
    }
    
    public static function renderMeasures() {
        if (! count(self::$measures)) return;
        echo '<table>';
        $starttime = self::$measures[0]['timestamp'];
        $lasttime = $starttime;
        foreach (self::$measures as $measure) {
            echo '<tr><td>'.number_format($measure['timestamp']-$starttime, 5,'.','').'</td>';
            echo '<td>+'.number_format($measure['timestamp']-$lasttime, 5,'.','').'</td>';
            echo '<td>'.$measure['text'];
            if ($measure['operation_count']) echo ' (average '.number_format(($measure['timestamp']-$lasttime)/$measure['operation_count'],5).') (ops pr. sec '.number_format(1/(($measure['timestamp']-$lasttime)/$measure['operation_count']),2,'.','').')';
            echo '</td></tr>';
            $lasttime = $measure['timestamp'];
        }
        echo '</table>';
    }
    
    public static function logMeasures() {
        if (! count(self::$measures)) return;
        $log = new Log('measure', array('10r', '10r', '50'));
        $log->log('Elapsed', 'Prev-now', 'Logtext');
        $starttime = self::$measures[0]['timestamp'];
        $lasttime = $starttime;
        foreach (self::$measures as $measure) {
            $entries = array();
            if ($measure['operation_count']) $log->log(number_format($measure['timestamp']-$starttime, 5,'.',''),number_format($measure['timestamp']-$lasttime, 5,'.',''),$measure['text'], '(average '.number_format(($measure['timestamp']-$lasttime)/$measure['operation_count'],5).') (ops pr. sec '.number_format(1/(($measure['timestamp']-$lasttime)/$measure['operation_count']),2,'.','').')');
            else $log->log(number_format($measure['timestamp']-$starttime, 5,'.',''),number_format($measure['timestamp']-$lasttime, 5,'.',''),$measure['text']);
            $lasttime = $measure['timestamp'];
        }
    }
    
    /**
     * Set a time limit for measuring. If this is exceeded and a measure is taken
     * the script will abort and display the measure output
     * @param int $seconds
     */
    public static function setMeasureTimeLimit($seconds = 60) {
        self::checkParams($seconds, 'int');
        self::$measure_time_limit = $seconds;
        self::$measure_time_start = time();
    }

    /**
     * Shutdown callback function
     */
    public static function shutdown() {
        // Release active semaphores
        if (class_exists('Platform\\Semaphore')) {
            Semaphore::releaseAll();
        }
        // Check if we should add to the recalculation file
        Datarecord::saveRequestedCalculations();
    }
}
