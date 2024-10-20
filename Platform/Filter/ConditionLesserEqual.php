<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;

class ConditionLesserEqual extends Condition {

    public function __construct(string $fieldname, $value) {
        $this->fieldname = $fieldname;
        if ($value instanceof Datarecord) $value = $value->getKeyValue();
        $this->value = $value;
        $this->type = new \Platform\Datarecord\Type($fieldname);
    }
    
    public function getAsArray(): array {
        return ['type' => 'LesserEqual', 'fieldname' => $this->fieldname, 'value' => $this->type->getJSONValue($this->value)];
    }
    
    public function getSQLFragment(): string {
        if ($this->type->getStoreLocation() != \Platform\Datarecord\Type::STORE_DATABASE) {
            $this->setNoSQL();
            return true;
        }
        $sql = $this->type->filterLesserEqualSQL($this->value);
        if ($sql === false) {
            $this->setNoSQL();
            return 'TRUE';
        }
        return $sql;
    }
    
    protected function manualMatch(Datarecord $datarecord, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return $this->type->filterLesserEqual($datarecord->getRawValue($this->fieldname), $this->value);
    }
    
    public function validate() {
        if (! $this->type) return ['No type attached to condition'];
        return true;
    }
}