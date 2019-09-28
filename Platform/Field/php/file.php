<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

pagestart('File upload', '/Platform/Field/js/file.js');

$current_file_name = '';

if ($_POST['action'] == 'send_file') {
    foreach ($_FILES as $file) {
        // Get a temp file name
        if (! $_POST['temp_file_name']) $_POST['temp_file_name'] = Platform\File::getTempFilename();
        $file_name_only = substr($_POST['temp_file_name'], strrpos($_POST['temp_file_name'], '/')+1);
        // Move file here
        move_uploaded_file($file['tmp_name'], $_POST['temp_file_name']);
        // Copy vital information back to form
        echo '<script language="javascript" type="text/javascript">';
        echo '$(\'#'.$_GET['formname'].' input[name="'.$_GET['fieldname'].'__mimetype"]\', window.parent.document).val(\''.$file['type'].'\');';
        echo '$(\'#'.$_GET['formname'].' input[name="'.$_GET['fieldname'].'__originalfile"]\', window.parent.document).val(\''.$file['name'].'\');';
        echo '$(\'#'.$_GET['formname'].' input[name="'.$_GET['fieldname'].'__tempfile"]\', window.parent.document).val(\''.$file_name_only.'\');';
        echo '$(\'#'.$_GET['formname'].' input[name="'.$_GET['fieldname'].'__status"]\', window.parent.document).val(\'changed\');';
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
    echo '$(\'#'.$_GET['formname'].' input[name="'.$_GET['fieldname'].'__status"]\', window.parent.document).val(\'removed\');';
    echo '</script>';
} elseif ($_GET['currentfileid']) {
    $file = new Platform\File();
    $file->loadForRead($_GET['currentfileid']);
    $current_file_name = $file->filename;
} elseif ($_GET['originalfile']) {
    $current_file_name = $_GET['originalfile'];
}

if ($current_file_name) {
    echo '<b>'.$current_file_name.'</b>';
    echo ' (<span style="color: red" id="file_delete">Delete</span>)';
    echo '<form method="post" id="file_delete_form">';
    echo '<input type="hidden" name="temp_file_name" value="'.$_POST['temp_file_name'].'">';
    echo '<input type="hidden" name="action" value="delete_file">';
    echo '</form>';
}

echo '<form method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="action" value="send_file">';
// TODO: Don't expose local path in HTML
echo '<input type="hidden" name="temp_file_name" value="'.$_POST['temp_file_name'].'">';
echo '<input name="file" type="file" class="w3-input">';
echo '</form>';

pageend();