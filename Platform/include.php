<?php
$configfile = $_SERVER['DOCUMENT_ROOT'].'/../platform_config.php';

require_once $configfile;

ini_set('display_errors', 'On');
error_reporting(E_ALL & ~E_NOTICE);

// Register autoloader
spl_autoload_register("platformAutoLoad");

session_start();

// Register shutdown
register_shutdown_function('Platform\\Errorhandler::shutdown');

// Register error handler
set_error_handler('Platform\\Errorhandler::handler');

umask(002);

function platformAutoLoad($class) {
    if (preg_match('/^(.*)\\\\([A-Z][A-Z]?[a-z0-9]*)([A-Z]*.*)$/', $class, $match)) {
        if ($match[3]) $file = __DIR__.'/../'.$match[1].'/'.$match[2].'/'.$match[3].'.php';
        else $file = __DIR__.'/../'.$match[1].'/'.$match[2].'/'.strtolower($match[2]).'.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Load includes
Platform\Design::queueJSFile('/Platform/Jquery/js/jquery.js');
Platform\Design::queueJSFile('/Platform/Jquery/js/jquery-ui.min.js');
Platform\Design::queueJSFile('/Platform/Design/js/general.js');

Platform\Design::queueJSFile('/Platform/Datarecord/js/helper.js');
Platform\Design::queueJSFile('/Platform/Dialog/js/dialog.js');
Platform\Design::queueJSFile('/Platform/Menu/js/menuitem.js');
Platform\Design::queueJSFile('/Platform/Form/js/form.js');
Platform\Design::queueJSFile('/Platform/Form/js/autosize.js');

Platform\Design::queueJSFile('/Platform/Field/js/multiplier.js');
Platform\Design::queueJSFile('/Platform/Field/js/combobox.js');
Platform\Design::queueJSFile('/Platform/Field/js/texteditor.js');
Platform\Design::queueCSSFile('/Platform/Field/css/texteditor.css');

Platform\Design::queueJSFile('/Platform/Design/js/greyout.js');
Platform\Design::queueCSSFile('/Platform/Design/css/greyout.css');

Platform\Design::queueJSFile('/Platform/Table/js/table.js');

Platform\Design::queueJSFile('https://unpkg.com/tabulator-tables@4.5.0/dist/js/tabulator.min.js');
Platform\Design::queueCSSFile('https://unpkg.com/tabulator-tables@4.5.0/dist/css/tabulator.min.css');

Platform\Design::queueJSFile('https://cdn.jsdelivr.net/npm/summernote@0.8.16/dist/summernote-lite.min.js');
Platform\Design::queueCSSFile('https://cdn.jsdelivr.net/npm/summernote@0.8.16/dist/summernote-lite.min.css');




function platformInitialize() {
    Instance::ensureInDatabase();
}