<?php
namespace Platform;

class Administrator {
    
    public static function isLoggedIn() {
        global $platform_configuration;
        return $_SESSION['platform']['administrator_password'] == $platform_configuration['administrator_password'];
    }
    
    public static function checkLogin() {
        global $platform_configuration;
        if (! $platform_configuration['administrator_password']) trigger_error('Please set the <i>administrator_password</i> configuration variable.', E_USER_ERROR);
        if (! self::isLoggedIn()) {
            $form = new Form('administrator_login_form');
            $form->addField(new FieldPassword('Password', 'administrator_password', array('required' => true)));
            $form->addField(new FieldSubmit('Continue', 'save'));
            
            if ($form->isSubmitted() && $form->validate()) {
                $values = $form->getValues();
                if ($values['administrator_password'] == $platform_configuration['administrator_password']) {
                    $_SESSION['platform']['administrator_password'] = $values['administrator_password'];
                    return true;
                }
                $form->getFieldByName('administrator_password')->triggerError('Invalid password');
            }
            
            \Platform\Page::renderPagestart('Administrator login required');
            echo '<p>Log in as administrator to continue.';
            $form->render();
            \Platform\Page::renderPageend();
            exit();
        }
    }
    
}