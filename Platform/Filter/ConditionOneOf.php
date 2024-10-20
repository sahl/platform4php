<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;

class ConditionOneOf extends Condition {

    public function __construct(string $fieldname, array $values) {
        $this->fieldname = $fieldname;
        $this->value = $values;
        $this->type = new \Platform\Datarecord\Type($fieldname);
    }
    
    public function getAsArray(): array {
        $packed_array = [];
        foreach ($this->value as $v) {
            $packed_array[] = $this->type->getJSONValue($v);
        }
        return ['type' => 'OneOf', 'fieldname' => $this->fieldname, 'value' => $packed_array];
    }
    
    public function getSQLFragment(): string {
        if ($this->type->getStoreLocation() != \Platform\Datarecord\Type::STORE_DATABASE) {
            $this->setNoSQL();
            return true;
        }
        $sql = $this->type->filterOneOfSQL($this->value);
        if ($sql === false) {
            $this->setNoSQL();
            return 'TRUE';
        }
        return $sql;
    }
    
    protected function manualMatch(Datarecord $datarecord, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return $this->type->filterOneOf($datarecord->getRawValue($this->fieldname), $this->value);
    }
    
    public function validate() {
        if (! $this->type) return ['No type attached to condition'];
        return true;
    }
}