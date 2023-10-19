<?php
namespace Platform;
// Check if we can decide a root
if (! $_SERVER['DOCUMENT_ROOT']) die('Couldn\'t read $_SERVER[\'DOCUMENT_ROOT\']');

use Platform\Page\Page;
use Platform\Security\Accesstoken;
use Platform\Security\Administrator;
use Platform\Server\Instance;

// Check for configuration file
$configuration_file = install_get_config_file_name();
if (! file_exists($configuration_file)) {
    header('location: /install/install.php');
    exit;
}

// Check if platform is there
$include_file = $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';
if (! file_exists($include_file)) die('Couldn\'t locate Platform4PHP in '.$_SERVER['DOCUMENT_ROOT'].'/Platform/');

include $include_file;

// Destroy any present instance
if (Instance::getActiveInstanceID()) {
    Accesstoken::destroySession();
}

// Check administrator login if configured.
Administrator::checkLogin ();

Page::renderPagestart('Platform4PHP', [], ['install.css']);

echo '<div class="content">';
echo '<h1>Platform4PHP</h1>';
echo '<p>...is installed on this server. You can now start developing your application.';
echo '<ul>';
echo '<li><a href="install.php">Change configuration settings</a>';
echo '<li><a href="/demo/">Go to the demo</a>';
echo '</ul>';
echo '</div>';

Page::renderPageend();

function install_get_config_file_name() {
    return install_get_parent_dir().'/platform_config.php';
}

function install_get_parent_dir() {
    $root = $_SERVER['DOCUMENT_ROOT'];
    // Strip trailing slash (if any)
    if (substr($root,-1) == '/') $root = substr($root,0,-1);
    // Go one dir up
    return substr($root, 0, strrpos($root,'/'));
}
