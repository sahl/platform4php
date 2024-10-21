<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;

class ConditionMatch extends Condition {

    public function __construct(string $fieldname, $value) {
        $this->fieldname = $fieldname;
        $this->value = $value;
        $this->type = new \Platform\Datarecord\Type($fieldname);
    }
    
    public function getAsArray(): array {
        return ['type' => 'Match', 'fieldname' => $this->fieldname, 'value' => $this->type->getJSONValue($this->value)];
    }
    
    public function getSQLFragment(): string {
        if (! in_array($this->type->getStoreLocation(), [\Platform\Datarecord\Type::STORE_DATABASE, \Platform\Datarecord\Type::STORE_SUBFIELDS])) {
            var_dump($this->type);
            die('dbg 3');
            $this->setNoSQL();
            return true;
        }
        $sql = $this->type->filterMatchSQL($this->type->parseValue($this->value));
        if ($sql === false) {
            $this->setNoSQL();
            return 'TRUE';
        }
        return $sql;
    }
    
    protected function manualMatch(Datarecord $datarecord, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return $this->type->filterMatch($datarecord->getRawValue($this->fieldname), $this->value);
    }
    
    public function validate() {
        if (! $this->type) return ['No type attached to condition'];
        return true;
    }
}