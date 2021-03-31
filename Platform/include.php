<?php
use Platform\Utilities\Translation;
use Platform\Page;

$configfile = $_SERVER['DOCUMENT_ROOT'].'/../platform_config.php';

require_once $configfile;

// We need to load some classes before the autoloader can work
$preload_list = array(
    '/Platform.php',
    '/Utilities/Errorhandler.php',
    '/DatarecordReferable.php',
    '/Datarecord.php',
    '/Server/Instance.php',
    '/Utilities/Translation.php',
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
register_shutdown_function('Platform\\Utilities\\Errorhandler::shutdown');

// Register error handler
set_error_handler('Platform\\Utilities\\Errorhandler::handler');

umask(002);
// INCLUDES
        
// Translation system
Page::queueJSFile('/Platform/Utilities/js/translation.js');
        
// Jquery        
Page::queueJSFile('/Platform/Jquery/js/jquery.js');
Page::queueJSFile('/Platform/Jquery/js/jquery-ui.min.js');
Page::queueJSFile('/Platform/Jquery/js/serialize2json.js');

Page::queueCSSFile('/Platform/Jquery/css/jquery-ui.css');

// General Platform
Page::queueJSFile('/Platform/Page/js/general.js');
Page::queueCSSFile('/Platform/Page/css/platform.css');
Page::queueJSFile('/Platform/Page/js/menuitem.js');

// Components
Page::queueCSSFile('/Platform/UI/css/component.css');
Page::queueJSFile('/Platform/UI/js/component.js');
Page::queueJSFile('/Platform/UI/js/dialog.js');

// Font awesome
Page::queueCSSFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');

function PlatformAutoLoad($class) {
    // Delve root from current location
    $root = substr(__DIR__,0,strrpos(__DIR__, '/'));
    // Find wanted filename
    $requested_file = $root.'/'.str_replace('\\', '/', $class).'.php';
    if (file_exists($requested_file)) {
        require_once $requested_file;
        if (Translation::isEnabled()) Translation::prepareTranslationsForFile ($requested_file);
        return;
    }
}

function platformInitialize() {
    Instance::ensureInDatabase();
}