<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

$form = new \Platform\Form('login_form');
$form->addField(new \Platform\FieldText('Company name', 'company_name', array('required' => true)));
$form->addField(new \Platform\FieldText('Your email', 'email', array('required' => true)));
$form->addField(new \Platform\FieldPassword('Your password', 'password', array('required' => true)));
$form->addField(new \Platform\FieldSubmit('Login', 'login'));

if ($form->isSubmitted() && $form->validate()) {
  $values = $form->getValues();
  $instance = \People\Instance::getByTitle($values['company_name']);
  if (! $instance->isInDatabase()) {
    $form->getFieldByName('company_name')->triggerError('We don\'t have such an instance!');
  } else {
    $instance->login($values['email'], $values['password'], '/application/');
    $form->getFieldByName('email')->triggerError('Invalid username or password');
    $form->getFieldByName('password')->triggerError('Invalid username or password');
  }
}



Platform\Design::renderPagestart('Log in');

echo '<h1>Log in</h1>';

$form->render();

echo '<p><a href="register.php">Press here to register instead</a>';

Platform\Design::renderPageend();

