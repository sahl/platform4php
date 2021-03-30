<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

$form = new \Platform\Form('login_form');
$form->addField(new \Platform\Form\TextField('Company name', 'company_name', array('required' => true)));
$form->addField(new \Platform\Form\TextField('Your email', 'email', array('required' => true)));
$form->addField(new \Platform\Form\PasswordField('Your password', 'password', array('required' => true)));
$form->addField(new \Platform\Form\SubmitButton('Login', 'login'));

if ($form->isSubmitted() && $form->validate()) {
  $values = $form->getValues();
  $instance = \People\Instance::getByTitle($values['company_name']);
  if (! $instance->isInDatabase()) {
    $form->getFieldByName('company_name')->triggerError('We don\'t have such an instance!');
  } else {
    $instance->loginAndContinue($values['email'], $values['password'], '/application/');
    $form->getFieldByName('email')->triggerError('Invalid username or password');
    $form->getFieldByName('password')->triggerError('Invalid username or password');
  }
}

Platform\Page::renderPagestart('Log in');

echo '<h1>Log in</h1>';

$form->render();

echo '<p><a href="register.php">Press here to register instead</a>';

Platform\Page::renderPageend();

