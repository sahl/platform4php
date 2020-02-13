<?php

$platform_configuration = array(
    // Database info
    'global_database_server' => 'GLOBAL DATABASE SERVER NAME',
    'global_database_username' => 'GLOBAL DATABASE USER NAME',
    'global_database_password' => 'GLOBAL DATABASE PASSWORD',
    'global_database_name' => 'GLOBAL DATABASE NAME',
    
    'local_database_server' => 'INSTANCE DATABASE SERVER NAME',
    'local_database_username' => 'INSTANCE DATABASE USER NAME',
    'local_database_password' => 'INSTANCE DATABASE PASSWORD',

    'instance_database_name' => 'INSTANCE DATABASE PREFIX NAME',
    
    
    // Mail info
    'mail_type' => 'smtp', // smtp or mail
    'smtp_server' => 'MAIL SERVER',
    'smtp_user' => 'MAIL USER NAME',
    'smtp_password' => 'MAIL PASSWORD',
    

    // Directories
    'dir_store' => 'DIR FOR STORAGE FILES',
    'dir_temp' => 'DIR FOR TEMP FILES',
    'dir_log' => 'DIR FOR LOGS',

    
    // Misc configuration
    'administrator_password' => 'THE ADMINISTRATOR INTERFACE PASSWORD',
    'url_server_talk' => '/Platform/Server/php/server_talk.php',
    'password_salt' => 'SALT FOR PASSWORDS.',
    
    
    // Microbizz
    'microbizz_public_id' => 'MICROBIZZ PUBLIC ID',
    'microbizz_secret_token' => 'MICROBIZZ SECRET TOKEN'
);