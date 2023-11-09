<?php
namespace Platform\Datarecord;
/**
 * Type class for primary key
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class KeyType extends IntegerType {

    public function __construct(string $name, string $title = '', array $options = []) {
        parent::__construct($name, $title, $options);
        $this->setPrimaryKey();
    }
}

