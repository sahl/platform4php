<?php
namespace People;

Class Instance extends \Platform\Instance {

    public function initializeDatabase() {
        parent::initializeDatabase();
        Department::ensureInDatabase();
        Employee::ensureInDatabase();
    }

}
