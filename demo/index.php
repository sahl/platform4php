<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

pagestart('Platform demo');

echo '<div class="w3-container w3-teal">';
echo '<h1>Platform demo</h1>';
echo '</div>';

echo '<div class="w3-container w3-center w3-padding-16">';

echo '<div class="w3-bar">';
echo '<button class="w3-button w3-black w3-hover-teal" data-destination="create/">Create instance</button> ';
echo '<button class="w3-button w3-black w3-hover-teal" data-destination="login/">Log into instance</button> ';
echo '</div>';

echo '</div>';

echo '<div class="w3-container w3-gray" style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

pageend();