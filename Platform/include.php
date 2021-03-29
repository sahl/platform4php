<?php
use Platform\Translation;
use Platform\Page;

$configfile = $_SERVER['DOCUMENT_ROOT'].'/../platform_config.php';

require_once $configfile;

// We need to load some classes before the autoloader can work
$preload_list = array(
    '/Errorhandler/errorhandler.php',
    '/Datarecord/Referable.php',
    '/Datarecord/datarecord.php',
    '/Instance/instance.php',
    '/Translation/translation.php',
);
// Load scripts
foreach ($preload_list as $script) {
    require_once __DIR__.$script;
}

// Register autoloader
spl_autoload_register("PlatformAutoLoad");
session_start();

// Load languages
if (Translation::isEnabled()) {
    Translation::prepareTranslationsForFile($_SERVER['PHP_SELF']);
    foreach ($preload_list as $script) Translation::prepareTranslationsForFile(__DIR__.$script);
}

// Register shutdown
register_shutdown_function('Platform\\Errorhandler::shutdown');

// Register error handler
set_error_handler('Platform\\Errorhandler::handler');

umask(002);
// INCLUDES
        
// Translation system
Page::queueJSFile('/Platform/Translation/js/translation.js');
        
// Jquery        
Page::queueJSFile('/Platform/Jquery/js/jquery.js');
Page::queueJSFile('/Platform/Jquery/js/jquery-ui.min.js');
Page::queueJSFile('/Platform/Jquery/js/serialize2json.js');

Page::queueCSSFile('/Platform/Jquery/css/jquery-ui.css');

// General Platform
Page::queueJSFile('/Platform/Page/js/general.js');
Page::queueCSSFile('/Platform/Page/css/platform.css');
Page::queueJSFile('/Platform/Menu/js/menuitem.js');

// Components
Page::queueCSSFile('/Platform/Component/css/component.css');
Page::queueJSFile('/Platform/Component/js/component.js');
Page::queueJSFile('/Platform/Dialog/js/dialog.js');

// Font awesome
Page::queueCSSFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');

function PlatformAutoLoad($class) {
    // Delve root from current location
    $root = substr(__DIR__,0,strrpos(__DIR__, '/'));
    if (preg_match('/^(.*)\\\\([A-Z][A-Z]?[a-z0-9]*)([A-Z]*.*)$/', $class, $match)) {
        $match[1] = str_replace('\\', '/', $match[1]);
        if ($match[3]) $file = $root.'/'.$match[1].'/'.$match[2].'/'.$match[3].'.php';
        else $file = $root.'/'.$match[1].'/'.$match[2].'/'.strtolower($match[2]).'.php';
        if (file_exists($file)) {
            require_once $file;
            if (Translation::isEnabled()) Translation::prepareTranslationsForFile ($file);
            return;
        }
    }
}

function platformInitialize() {
    Instance::ensureInDatabase();
}