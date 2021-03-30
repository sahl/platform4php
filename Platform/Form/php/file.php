<?php
include $_SERVER['DOCUMENT_ROOT'].'/Platform/include.php';

use Platform\Page;
use Platform\File;

Page::renderPagestart('File upload', ['/Platform/Field/js/file.js']);

$current_file_name = '';

if ($_POST['action'] == 'send_file') {
    foreach ($_FILES as $file) {
        // Get a temp file name
        if (! $_POST['temp_file_name']) $_POST['temp_file_name'] = File::getTempFilename();
        $file_name_only = substr($_POST['temp_file_name'], strrpos($_POST['temp_file_name'], '/')+1);
        // Move file here
        move_uploaded_file($file['tmp_name'], $_POST['temp_file_name']);
        // Copy vital information back to form
        echo '<script language="javascript" type="text/javascript">';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[mimetype]"]\', window.parent.document).val(\''.$file['type'].'\');';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[original_file]"]\', window.parent.document).val(\''.$file['name'].'\');';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[temp_file]"]\', window.parent.document).val(\''.$file_name_only.'\');';
        echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[status]"]\', window.parent.document).val(\'changed\');';
        echo '</script>';
        $current_file_name = $file['name'];
    }
} elseif ($_POST['action'] == 'delete_file') {
    // Remove temporary file (if present)
    if ($_POST['temp_file_name'] && file_exists($_POST['temp_file_name'])) {
        unlink($_POST['temp_file_name']);
    }
    $_POST['temp_file_name'] == '';
    // Copy vital information back to form
    echo '<script language="javascript" type="text/javascript">';
    echo '$(\'#'.$_GET['form_name'].' input[name="'.$_GET['field_name'].'[status]"]\', window.parent.document).val(\'removed\');';
    echo '</script>';
} elseif ($_GET['file_id']) {
    $file = new File();
    $file->loadForRead($_GET['file_id']);
    $current_file_name = $file->filename;
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
    echo '<input type="hidden" name="temp_file_name" value="'.$_POST['temp_file_name'].'">';
    echo '<input type="hidden" name="action" value="delete_file">';
    echo '</form>';
} else {
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="send_file">';
    // TODO: Don't expose local path in HTML
    echo '<input type="hidden" name="temp_file_name" value="'.$_POST['temp_file_name'].'">';
    echo '<input name="file" type="file">';
    echo '</form>';
}

echo '<div id="upload_message" class="platform_invisible">File is uploading...</div>';

Page::renderPageend();