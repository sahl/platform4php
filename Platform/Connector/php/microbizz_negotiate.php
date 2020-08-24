<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

// Switch to requested instance
$instance = new \Platform\Instance();
$instance->loadForRead($_GET['instance_id']);
if (! $instance->isInDatabase()) die(0);
$instance->activate();

$token = \Platform\Accesstoken::getByTokencode($_GET['token']);
if (! $token->isValid()) die(0);

\Platform\File::ensureFolderInStore(\Platform\File::getFullFolderPath('temp'));
$fh = fopen(\Platform\File::getFullFolderPath('temp').'microbizz_credentials_user_'.$_GET['userid'], 'w');
if ($fh !== false) {
    fwrite($fh, $_POST['endpoint']."\n");
    fwrite($fh, $_POST['contract']."\n");
    fwrite($fh, $_POST['accesstoken']);
    fclose($fh);
} else die(0);

// Solve challenge
echo \Platform\ConnectorMicrobizz::solveChallenge($_POST['challenge']);
