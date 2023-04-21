<?php

use PHPMailer\PHPMailer\PHPMailer;
use Platform\Form;
use Platform\Page;
use Platform\Platform;
use Platform\Security\Administrator;
use Platform\Server;
use Platform\Utilities\Database;
use Platform\Utilities\Mail;

// Check if we can decide a root
if (! $_SERVER['DOCUMENT_ROOT']) die('Couldn\'t read $_SERVER[\'DOCUMENT_ROOT\']');

// Check if platform is there
$include_file = $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';
if (! file_exists($include_file)) die('Couldn\'t locate Platform4PHP in '.$_SERVER['DOCUMENT_ROOT'].'Platform/ (Are you missing a / on DOCUMENT_ROOT?)');

// Direct test
$perform_test = \Platform\Utilities::arraySafeRead($_GET, 'dotest') == 1;

// Check for configuration file
$configuration_file = install_get_config_file_name();
if (! file_exists($configuration_file)) {
    $perform_test = false;
    // Try to write a new bare configuration file
    $fh = fopen($configuration_file, 'w');
    if ($fh === false) die('Couldn\'t open '.$configuration_file.' for writing. We need to be able to do that!');
    fwrite($fh, "<?php\n\$platform_configuration = array();");
    fclose($fh);
}
include $include_file;

// Check administrator login if configured.
if (Platform::getConfiguration('administrator_password')) Administrator::checkLogin ();

// Destroy any present instance
if (Server\Instance::getActiveInstanceID()) {
    \Platform\Security\Accesstoken::destroySession();
}

// Get install configuration form
$install_form = Form::Form('install_form', 'install.frm');

// Add default values to form
$install_form->setValues(array(
    'global_database_server' => 'localhost',
    'local_database_server' => 'localhost',
    'url_server_talk' => '/Platform/Server/php/server_talk.php',
    'dir_store' => install_get_parent_dir().'/store/',
    'dir_temp' => '/var/tmp/',
    'dir_log' => '/var/log/platform/',
    'password_salt' => md5(rand())
));

$install_form->setLabelWidth(275);

// Add configuration values to form
$install_form->setValues($platform_configuration);

// Initiate error array
$errors = array();

if ($install_form->isSubmitted() && $install_form->validate()) {
    $perform_test = true;
    Platform::setConfigurationFromArray($install_form->getValues(), true);
}

if ($perform_test) {
    // Time to test
    install_test_all($errors);
    
    // Continue if no errors
    if (! count($errors)) {
        // Delve and ensure server
        $server_id = Server::ensureThisServer();
        if (! $server_id) $errors[] = 'Could not identify server. Make sure HTTP_HOST is set.';
        else {
            Platform::setConfiguration('server_id', $server_id);
            if (! Platform::writeConfigurationFile()) $errors[] = 'Couldn\'t open '.$configuration_file.' for writing. We need to be able to do that!';
            else {
                sleep(1);
                Page::renderPagestart('Continue');
                echo "<script type=\"text/javascript\">\ndocument.location = '/install/index.php';\n</script><a href=\"index.php\">Continue</a>";
                Page::renderPageend();
                exit;
            }
        }
    }
}

Page::renderPagestart('Install Platform4PHP', ['install.js'], ['install.css']);

if ($errors) {
    echo '<div class="errors">We encountered one or more errors trying to get Platform4PHP to work!<ul><li>';
    echo implode('<li>', $errors);
    echo '</ul>Please adjust the configuration.</div>';
}

$install_form->render();

Page::renderPageend();




function install_get_config_file_name() {
    return install_get_parent_dir().'/platform_config.php';
}

function install_get_parent_dir() {
    $root = $_SERVER['DOCUMENT_ROOT'];
    // Strip trailing slash (if any)
    if (substr($root,-1) == '/') $root = substr($root,0,-1);
    // Go one dir up
    return substr($root, 0, strrpos($root,'/'));
}

function install_test_all(array &$errors) {
    // Try global connect
    $result = Database::connectGlobal();
    if ($result) {
        $result = Database::ensureGlobalDatabase();
        if (! $result) $errors[] = 'The global database '.Platform::getConfiguration('global_database_name').' did not exist, and we couldn\'t create it. Error: '.Database::getLastGlobalError();
        // Try local connect
        $result = Database::connectLocal();
        if ($result) {
            // Now see if we can create a local database
            $temp_database_name = Platform::getConfiguration('instance_database_name').'_tempdatabase';
            $result = Database::localQuery("CREATE DATABASE ".$temp_database_name, false);
            if ($result) {
                $result = Database::localQuery("USE ".$temp_database_name, false);
                if ($result) {
                    $result = Database::localQuery("CREATE TABLE temp_table (id INT NOT NULL)", false);
                    if (! $result) $errors[] = 'Could not create table in newly created database. Error: '.Database::getLastLocalError();
                    else {
                        $result = Database::localQuery("INSERT INTO temp_table VALUES (24)", false);
                        if (! $result) $errors[] = 'Could not insert into newly created database. Error: '.Database::getLastLocalError();
                        else {
                            $result = Database::localQuery("SELECT id FROM temp_table", false);
                            if (! $result) $errors[] = 'Could not select from newly created database. Error: '.Database::getLastLocalError();
                        }
                    }
                } else {
                    $errors[] = 'Could not use newly created database. Error: '.Database::getLastLocalError();
                }
                $result = Database::localQuery("DROP DATABASE ".$temp_database_name, false);
                if (! $result) $errors[] = 'Could not drop newly created database. Please remove database '.$temp_database_name.' manually. Error: '.Database::getLastLocalError();
            } else {
                $errors[] = 'Could not create a new database on the local connection. Does it have the correct permissions? Error: '.Database::getLastLocalError();
            }
        } else {
            $errors[] = 'Could not connect local database '.Platform::getConfiguration('local_database_server').' ('.Platform::getConfiguration('local_database_username').'/XXXXXXX)';
        } 
    } else {
        $errors[] = 'Could not connect global database '.Platform::getConfiguration('global_database_server').' ('.Platform::getConfiguration('global_database_username').'/XXXXXXX)';
    }
    
    // Check directories
    foreach (array('dir_store', 'dir_temp', 'dir_log') as $dir_name) {
        $directory = Platform::getConfiguration($dir_name);
        if (substr($directory,-1,1) != '/') {
            $errors[] = 'Invalid directory '.$directory.'. Directories must end with a /';
            continue;
        }
        
        if (! is_dir($directory)) $errors[] = $directory.' does not exists. Please create this directory.';
        else {
            $full_path_testfile = $directory.'testfile.tmp';
            // Try to write
            $fh = @fopen($full_path_testfile, 'w');
            if ($fh) {
                fwrite($fh, 'test');
                fclose($fh);
                // Try to read
                $fh = @fopen($full_path_testfile, 'r');
                if ($fh) {
                    $line = fread($fh,4);
                    fclose($fh);
                } else {
                    $errors[] = 'Could not read a file from directory '.$directory.'. Please check permissions.';
                }
                // Try to delete
                $result = @unlink($full_path_testfile);
                if (! $result) $errors[] = 'Could not delete test file '.$full_path_testfile.'. Please delete it manually and check permissions.';
                if ($dir_name == 'dir_store') {
                    $full_path_testdirectory = $directory.'testdir';
                    // Try to create a directory
                    $result = mkdir($full_path_testdirectory);
                    if ($result) {
                        $result = rmdir($full_path_testdirectory);
                        if (! $result) $errors[] = 'Could not delete directory '.$full_path_testdirectory.'. Please delete it manually and check permissions.';
                    } else {
                        $errors[] = 'Could not create subdirectory in '.$directory.'. Please check permissions.';
                    }
                }
            } else {
                $errors[] = 'Could not write a file in directory '.$directory.'. Please check permissions.';
            }
        }
    }
    
    // Check mail (if setup)
    if (Platform::getConfiguration('mail_type') && Platform::getConfiguration('mail_to')) {
        Mail::initPhpmailer();
        // Prepare mail
        $mailer = new PHPMailer(true);
        try {
            if (Platform::getConfiguration('mail_type') == 'smtp') {
                $mailer->isSMTP();
                $mailer->Host = Platform::getConfiguration('smtp_server');
                $mailer->Port = Platform::getConfiguration('smtp_port') ?: 587;
                if (Platform::getConfiguration('smtp_username')) {
                    $mailer->SMTPAuth = true;
                    $mailer->Username = Platform::getConfiguration('smtp_username');
                    $mailer->Password = Platform::getConfiguration('smtp_password');
                }
            } else {
                $mailer->isMail();
            }
            $mailer->CharSet = 'UTF-8';
            $mailer->isHTML(true);
            $mailer->setFrom(Platform::getConfiguration('mail_from'), 'Platform install script');
            $mailer->addAddress(Platform::getConfiguration('mail_to'), 'Platform test email receiver');
            $mailer->Subject = 'Platform SMTP mail is working';
            $mailer->Body = "<b>Platform SMTP mail is working</b><p>This mail was send from the install script for Platform. If you have received this mail, the mailer is working.";
            $result = $mailer->send();
            if (! $result)
                $errors[] = 'Could not send SMTP test mail to '.Platform::getConfiguration('smtp_to').'.';
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errors[] = 'Could not send SMTP test mail to '.Platform::getConfiguration('smtp_to').'. Error: '.$e->errorMessage();
        }
    }
    
    if (! count($errors)) {
        Server::ensureGlobalObjects();
    }
}
