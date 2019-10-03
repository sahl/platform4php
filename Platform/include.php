<?php
session_start();

$configfile = $_SERVER['DOCUMENT_ROOT'].'/config.php';

require_once $configfile;

ini_set('display_errors', 'On');
error_reporting(E_ALL & ~E_NOTICE);

// Register autoloader
spl_autoload_register("platformAutoLoad");

// Register shutdown
register_shutdown_function('Platform\\Errorhandler::shutdown');

// Register error handler
set_error_handler('Platform\\Errorhandler::handler');

function platformAutoLoad($class) {
    global $platform_configuration;
    
    if (preg_match('/^(.*)\\\\([A-Z][a-z0-9]*)([A-Z]*.*)$/', $class, $match)) {
        if ($match[3]) $file = __DIR__.'/../'.$match[1].'/'.$match[2].'/'.$match[3].'.php';
        else $file = __DIR__.'/../'.$match[1].'/'.$match[2].'/'.strtolower($match[2]).'.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Load includes
Platform\Design::queueJSFile('https://unpkg.com/tabulator-tables@4.4.0/dist/js/tabulator.min.js');
Platform\Design::queueJSFile('/Platform/Datarecord/js/helper.js');
Platform\Design::queueJSFile('/Platform/Form/js/form.js');
Platform\Design::queueJSFile('/Platform/Form/js/multiplier.js');
Platform\Design::queueJSFile('/Platform/Table/js/table.js');

Platform\Design::queueCSSFile('https://unpkg.com/tabulator-tables@4.4.0/dist/css/tabulator.min.css');


function platformInitialize() {
    Instance::ensureInDatabase();
}

// Conventience functions
function gq($query, $failonerror = true) {
    return Platform\Database::globalQuery($query, $failonerror);
}

function fq($query) {
    return Platform\Database::instanceFastQuery($query);
}

function q($query, $failonerror = true) {
    return Platform\Database::instanceQuery($query, $failonerror);
}

function fr($resultset) {
    return Platform\Database::getRow($resultset);
}

function gfq($query) {
    return Platform\Database::globalFastQuery($query);
}

function esc($string) {
    return Platform\Database::escape($string);
}

function pagestart($title, $jsfiles = array(), $cssfiles = array()) {
    Platform\Design::renderPagestart($title, $jsfiles, $cssfiles);
}

function pageend() {
    Platform\Design::renderPageend();
}

// Helper functions

/**
 * Removes an element from an array
 * @param array $array Array to check
 * @param mixed $element Element to remove
 */
function array_remove(&$array, $element) {
    $array = array_diff($array, array($element));
}

function valalt($value, $alternative1, $alternative2 = '') {
    return $value ? $value : ($alternative1 ? $alternative1 : $alternative2);
}