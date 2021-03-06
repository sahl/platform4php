<?php
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
spl_autoload_register("platformAutoLoad");

session_start();

// Load languages
if (\Platform\Translation::isEnabled()) {
    \Platform\Translation::prepareTranslationsForFile($_SERVER['PHP_SELF']);
    foreach ($preload_list as $script) \Platform\Translation::prepareTranslationsForFile(__DIR__.$script);
}
Platform\Page::queueJSFile('/Platform/Translation/js/translation.js');

// Register shutdown
register_shutdown_function('Platform\\Errorhandler::shutdown');

// Register error handler
set_error_handler('Platform\\Errorhandler::handler');

umask(002);

// Load includes
Platform\Page::queueJSFile('/Platform/Jquery/js/jquery.js');
Platform\Page::queueJSFile('/Platform/Jquery/js/jquery-ui.min.js');
Platform\Page::queueJSFile('/Platform/Jquery/js/serialize2json.js');
Platform\Page::queueJSFile('/Platform/Design/js/general.js');
Platform\Page::queueCSSFile('/Platform/Menu/css/menu.css');

Platform\Page::queueJSFile('/Platform/Dialog/js/dialog.js');
Platform\Page::queueJSFile('/Platform/Menu/js/menuitem.js');
Platform\Page::queueJSFile('/Platform/Form/js/form.js');
Platform\Page::queueJSFile('/Platform/Form/js/autosize.js');

Platform\Page::queueJSFile('/Platform/Field/js/multiplier.js');
Platform\Page::queueJSFile('/Platform/Field/js/combobox.js');
Platform\Page::queueJSFile('/Platform/Field/js/texteditor.js');
Platform\Page::queueCSSFile('/Platform/Field/css/texteditor.css');

Platform\Page::queueJSFile('/Platform/Component/js/component.js');

Platform\Page::queueJSFile('/Platform/Design/js/greyout.js');
Platform\Page::queueCSSFile('/Platform/Design/css/greyout.css');

Platform\Page::queueJSFile('https://unpkg.com/tabulator-tables@4.7.0/dist/js/tabulator.min.js');
Platform\Page::queueCSSFile('https://unpkg.com/tabulator-tables@4.7.0/dist/css/tabulator.min.css');

Platform\Page::queueJSFile('https://cdn.jsdelivr.net/npm/summernote@0.8.16/dist/summernote-lite.min.js');
Platform\Page::queueCSSFile('https://cdn.jsdelivr.net/npm/summernote@0.8.16/dist/summernote-lite.min.css');



function platformAutoLoad($class) {
    // Delve root from current location
    $root = substr(__DIR__,0,strrpos(__DIR__, '/'));
    if (preg_match('/^(.*)\\\\([A-Z][A-Z]?[a-z0-9]*)([A-Z]*.*)$/', $class, $match)) {
        $match[1] = str_replace('\\', '/', $match[1]);
        if ($match[3]) $file = $root.'/'.$match[1].'/'.$match[2].'/'.$match[3].'.php';
        else $file = $root.'/'.$match[1].'/'.$match[2].'/'.strtolower($match[2]).'.php';
        if (file_exists($file)) {
            require_once $file;
            if (\Platform\Translation::isEnabled()) \Platform\Translation::prepareTranslationsForFile ($file);
            return;
        }
    }
}

function platformInitialize() {
    Instance::ensureInDatabase();
}