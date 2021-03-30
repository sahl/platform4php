<?php
include $_SERVER['DOCUMENT_ROOT'].'Platform/include.php';

Platform\Security\Accesstoken::validateSession('/login/');

$form = new Platform\Form('search_form', 'search.form');

if ($form->isSubmitted()) {
    if ($form->validate()) {
        $table = Platform\Table::getTableFromClass('search_table', 'People\\Employee');

        $values = $form->getValues();

        $filter = new Platform\Filter('People\\Employee');
        if ($values['name']) $filter->addCondition( new Platform\ConditionLike('full_name', $values['name']));
        if ($values['min_age']) $filter->addCondition( new Platform\ConditionGreaterEqual('age', $values['min_age']));
        if ($values['max_age']) $filter->addCondition( new Platform\ConditionLesserEqual('age', $values['max_age']));

        $table->setFilter($filter);
    }
}

Platform\Page::renderPagestart('Search employees');

echo '<h1>Search employees</h1>';

$form->render();

if ($form->isSubmitted()) $table->render();

Platform\Page::renderPageend();
