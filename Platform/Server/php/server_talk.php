<?php
namespace Platform;
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

$input = file_get_contents("php://input");
$json = json_decode($input, true);

if ($json === null) exit;

$result = array(
    'status' => false,
    'error' => 'No command'
);

switch ($json['event']) {
    case 'create_instance':
        $server = Server::getThisServerID();
        if (! $server->isInDatabase()) {
            $result['error'] = 'Couldn\'t identify server.';
        } else {
            $class = $json['class'];
            $instance = $class::initialize($json['title'], $json['username'], $json['password'], $server->server_id);
            if (! $instance) $result['error'] = 'Could not initialize instance on remote server.';
            else $result = array(
                'status' => true,
                'instance_id' => $instance->instance_id
            );
        }
        break;
    case 'login':
        $class = $json['class'];
        $instance = new $class();
        $instance->loadForRead($json['instance_id']);
        $instance->activate();
        $access_token = $instance->tryLogin($json['username'], $json['password']);
        if ($access_token) {
            $result = array(
                'status' => true,
                'token_code' => $access_token->token_code
            );
        } else {
            $result['error'] = 'Invalid login '.$json['username'].' '.$json['password'];
        }
        break;
}

echo json_encode($result);