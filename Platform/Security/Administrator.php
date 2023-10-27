<?php
namespace Platform\Security;
/**
 * Handles administrator login for the Platform installation pages.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=administrator_class
 */

use Platform\Platform;
use Platform\Form\Form;
use Platform\Page\Page;
use Platform\Form\PasswordField;
use Platform\Form\SubmitButton;

class Administrator {
    
    /**
     * Check if the administrator is logged in
     * @return bool
     */
    public static function isLoggedIn() : bool {
        return $_SESSION['platform']['administrator_password'] == Platform::getConfiguration('administrator_password');
    }
    
    public static function checkLogin() {
        if (! Platform::getConfiguration('administrator_password')) trigger_error('Please set the <i>administrator_password</i> configuration variable.', E_USER_ERROR);
        if (! self::isLoggedIn()) {
            $form = Form::Form('administrator_login_form');
            $form->addField(PasswordField::Field('Password', 'administrator_password', array('required' => true)));
            $form->addField(SubmitButton::Field('Continue', 'save'));
            
            if ($form->isSubmitted() && $form->validate()) {
                $values = $form->getValues();
                if ($values['administrator_password'] == Platform::getConfiguration('administrator_password')) {
                    $_SESSION['platform']['administrator_password'] = $values['administrator_password'];
                    return true;
                }
                $form->getFieldByName('administrator_password')->triggerError('Invalid password');
            }
            
            Page::renderPagestart('Administrator login required');
            echo '<p>Log in as administrator to continue.';
            $form->render();
            Page::renderPageend();
            exit();
        }
    }
    
}