<?php
namespace People;

Class Instance extends \Platform\Server\Instance {

    public function initializeDatabase() {
        parent::initializeDatabase();
        Department::ensureInDatabase();
        Employee::ensureInDatabase();
    }
}
