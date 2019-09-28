<?php

namespace Platform;

class Errorhandler {

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
            case E_WARNING:
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
     * Shutdown callback function
     */
    public static function shutdown() {
        // Release active semaphores
        if (class_exists('Platform\\Semaphore')) {
            Semaphore::releaseAll();
        }
    }
}
