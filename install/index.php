<?php
namespace Platform;
// Check if we can decide a root
if (! $_SERVER['DOCUMENT_ROOT']) die('Couldn\'t read $_SERVER[\'DOCUMENT_ROOT\']');

use Platform\Security\Administrator;

// Check if platform is there
$include_file = $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';
if (! file_exists($include_file)) die('Couldn\'t locate Platform4PHP in '.$_SERVER['DOCUMENT_ROOT'].'/Platform/');

include $include_file;

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