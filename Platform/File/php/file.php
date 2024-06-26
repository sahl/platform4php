<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

use Platform\Server\Instance;
use Platform\File\File;

$code = 404;
$error = 'File not found (invalid URL)';

if (preg_match('/^\\/Platform\\/File\\/php\\/file\\.php\\/(\\d+?)\\/(\\d+?)\\//i', $_SERVER['PHP_SELF'], $matches)) {
    $instanceid = $matches[1];
    $file_id = $matches[2];
    if ($instanceid != Instance::getActiveInstanceID()) {
        $code = 403;
        $error = 'Not logged into requested instance.';
    } else {
        $file = new File();
        $file->loadForRead($file_id);
        if ($file->isInDatabase()) {
            if (! $file->canAccess()) {
                $code = 403;
                $error = 'Cannot access this file!';
            } else {
                // Serve file
                header('Content-Type: '.$file->mimetype);
                readfile($file->getCompleteFilename());
                exit;
            }
        }
    }
}
http_response_code($code);

echo $error;