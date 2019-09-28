<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

pagestart('Installer');

echo '<div class="w3-container w3-teal">';
echo '<h1>Installer</h1>';
echo '</div>';

App\Instance::ensureInDatabase();

echo '<div class="w3-container w3-padding-16 w3-text-gray">';
echo 'Done.';
echo '</div>';

echo '<div class="w3-container w3-gray" style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

pageend();