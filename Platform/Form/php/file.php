<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

use Platform\Page\Page;
use Platform\File;

Page::renderPagestart('File upload', ['/Platform/Form/js/file.js'], [], ['no_history' => true]);

if ($_POST['action'] == 'send_file') {
    foreach ($_FILES as $file) {
        // Get a temp file name
        if (! $_POST['temp_file_name']) $temp_file = File::getTempFilename();
        else $temp_file = File::getFullFolderPath('temp').basename($_POST['temp_file_name']);
        $_POST['temp_file_name'] = basename($temp_file);
        // Move file here
        move_uploaded_file($file['tmp_name'], $temp_file);
        // Copy vital information back to form
        echo '<script language="javascript" type="text/javascript">';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[mimetype]"]\', window.parent.document).val(\''.$file['type'].'\');';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[original_file]"]\', window.parent.document).val(\''.$file['name'].'\');';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[temp_file]"]\', window.parent.document).val(\''.basename($temp_file).'\');';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[status]"]\', window.parent.document).val(\'changed\');';
        echo '</script>';
        $current_file_name = $file['name'];
    }
} elseif ($_POST['action'] == 'delete_file') {
    // Remove temporary file (if present)
    $temp_file = File::getFullFolderPath('temp').basename($_POST['temp_file_name']);
    if ($_POST['temp_file_name'] && file_exists($temp_file)) {
        unlink($temp_file);
    }
    $_POST['temp_file_name'] = '';
    // Copy vital information back to form
    echo '<script language="javascript" type="text/javascript">';
    echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[status]"]\', window.parent.document).val(\'removed\');';
    echo '</script>';
} elseif ($_GET['file_id']) {
    $file = new File();
    $file->loadForRead($_GET['file_id']);
    $current_file_name = $file->canAccess() ? $file->filename : '';
} elseif ($_GET['original_file']) {
    $current_file_name = $_GET['original_file'];
}

if ($current_file_name) {
    if ($file instanceof File) {
        echo '<a href="'.$file->getURL().'" target="_blank"><img border=0 style="vertical-align: top;" src="'.File::getFiletypeURLByExtension(File::extractExtension($current_file_name)).'"></a>';
    } else {
        echo '<img style="vertical-align: top;" src="'.File::getFiletypeURLByExtension(File::extractExtension($current_file_name)).'">';
    }
    echo $current_file_name;
    echo ' <span class="fa fa-minus-circle" id="file_delete" style="color: red"> </span>';
    echo '<form method="post" id="file_delete_form">';
    echo '<input type="hidden" name="temp_file_name" value="'.basename($_POST['temp_file_name']).'">';
    echo '<input type="hidden" name="action" value="delete_file">';
    echo '</form>';
} else {
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="send_file">';
    echo '<input type="hidden" name="temp_file_name" value="'.basename($_POST['temp_file_name']).'">';
    echo '<input name="file" type="file">';
    echo '</form>';
}

echo '<div id="upload_message" class="platform_invisible">File is uploading...</div>';

Page::renderPageend();