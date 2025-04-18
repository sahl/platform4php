<?php
use Platform\Utilities\Translation;
use Platform\Page\Page;

$configfile = __DIR__.'/../../platform_config.php';

require_once $configfile;

if (! isset($platform_configuration) || ! is_array($platform_configuration)) $platform_configuration = [];

// We need to load some classes before the autoloader can work
$preload_list = array(
    '/Platform.php',
    '/Utilities/Errorhandler.php',
    '/Datarecord/DatarecordReferable.php',
    '/UI/Serializable.php',
    '/Datarecord/Datarecord.php',
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

// Set display time zone from session
Platform\Utilities\Time::setDisplayTimeZoneFromSession();
Platform\Utilities\Time::setDateAndTimeFormatFromSession();

// Set number format from session
\Platform\Utilities\NumberFormat::setFormatFromSession();

// Add number format for javascript
Page::addData('platform_number_format', \Platform\Utilities\NumberFormat::getFormat());
Page::addData('platform_component_io_url', \Platform\UI\Component::getIOUrl());
Page::addData('platform_time_zone', \Platform\Utilities\Time::getDisplayTimeZoneFromSession());


// Load languages
if (Translation::isEnabled()) {
    Translation::prepareTranslationsForFile($_SERVER['PHP_SELF']);
    foreach ($preload_list as $script) Translation::prepareTranslationsForFile(__DIR__.$script);
}

// Register shutdown
register_shutdown_function('Platform\\Utilities\\Errorhandler::shutdown');

// Register error handler
set_error_handler('Platform\\Utilities\\Errorhandler::handler');

// Jquery        
Page::queueJSFile('/Platform/Jquery/js/jquery.js');
Page::queueJSFile('/Platform/Jquery/js/jquery-ui.min.js');
Page::queueJSFile('/Platform/Jquery/js/serialize2json.js');

// General Platform
Page::queueJSFile('/Platform/Page/js/Platform.js');

// Translation system
Page::queueJSFile('/Platform/Utilities/js/Translation.js');

Page::queueCSSFile('/Platform/Jquery/css/jquery-ui.css');

Page::queueCSSFile('/Platform/Page/css/Platform.css');
Page::queueJSFile('/Platform/Page/js/MenuItem.js');

// Number format
Page::queueJSFile('/Platform/Utilities/js/NumberFormat.js');

// Time functions
Page::queueJSFile('/Platform/Utilities/js/Time.js');

// Components
Page::queueCSSFile('/Platform/UI/css/Component.css');
Page::queueJSFile('/Platform/UI/js/Component.js');
Page::queueJSFile('/Platform/Form/js/Form.js');
Page::queueJSFile('/Platform/UI/js/Dialog.js');

// Google loader
Page::queueJSFile('https://www.gstatic.com/charts/loader.js');

// Font awesome
Page::queueCSSFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');

// Include custom file if present
$custom_include = __DIR__.'/../include.php';
if (file_exists($custom_include)) include_once $custom_include;

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