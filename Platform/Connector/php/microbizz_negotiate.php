<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

use Platform\Security\Accesstoken;
use Platform\Server\Instance;
use Platform\File;
use Platform\Connector\Microbizz;

// Switch to requested instance
$instance = new Instance();
$instance->loadForRead($_GET['instance_id']);
if (! $instance->isInDatabase()) die(0);
$instance->activate();

$token = Accesstoken::getByTokencode($_GET['token']);
if (! $token->isValid()) die(0);

File::ensureFolderInStore(File::getFullFolderPath('temp'));
$fh = fopen(File::getFullFolderPath('temp').'microbizz_credentials_user_'.$_GET['userid'], 'w');
if ($fh !== false) {
    fwrite($fh, $_POST['endpoint']."\n");
    fwrite($fh, $_POST['contract']."\n");
    fwrite($fh, $_POST['accesstoken']);
    fclose($fh);
} else die(0);

// Solve challenge
echo Microbizz::solveChallenge($_POST['challenge']);
