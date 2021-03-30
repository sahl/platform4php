<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

$form = new \Platform\Form('registration_form');
$form->addField(new \Platform\Form\TextField('Company name', 'company_name', array('required' => true)));
$form->addField(new \Platform\Form\TextField('Your email', 'email', array('required' => true)));
$form->addField(new \Platform\Form\PasswordField('Your password', 'password', array('required' => true)));
$form->addField(new \Platform\Form\SubmitButton('Register', 'register'));

if ($form->isSubmitted() && $form->validate()) {
  $values = $form->getValues();
  $instance = \People\Instance::getByTitle($values['company_name']);
  if ($instance->isInDatabase()) {
    $form->getFieldByName('company_name')->triggerError('This company name is already claimed!');
  } else {
    $new_instance = \People\Instance::initialize($values['company_name'], $values['email'], $values['password']);
    $new_instance->login($values['user_name'], $values['password'], '/application/');
  }
}



Platform\Page::renderPagestart('Register');

echo '<h1>Register</h1>';

$form->render();

Platform\Page::renderPageend();

