<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

\Platform\Page\Page::queueJSFile('buttonlink.js');
\Platform\Page\Page::renderPagestart('Platform demo');

echo '<h1>Platform demo</h1>';


echo '<div>';
echo '<button data-destination="create/">Create instance</button> ';
echo '<button data-destination="login/">Log into instance</button> ';
echo '</div>';

echo '</div>';

echo '<div style="font-style: italic; font-size: 0.8em;">';
echo 'Platform';
echo '</div>';

\Platform\Page\Page::renderPageend();