<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

// Switch to requested instance
$instance = new \App\Instance();
$instance->loadForRead($_GET['instance_id']);
if (! $instance->isInDatabase()) die(0);
$instance->activate();

$token = \Platform\Accesstoken::getByTokencode($_GET['token']);
if (! $token->isValid()) die(0);

// Store the token
\Platform\ConnectorMicrobizz::setAccessToken($_POST['accesstoken']);

// Store the contract
\Platform\ConnectorMicrobizz::setContract($_POST['contract']);

// Store the endpoint
\Platform\ConnectorMicrobizz::setEndpoint($_POST['endpoint']);

// Solve challenge
echo \Platform\ConnectorMicrobizz::solveChallenge($_POST['challenge']);
