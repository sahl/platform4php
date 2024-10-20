<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;

class ConditionIsSet extends Condition {

    public function __construct(string $fieldname) {
        $this->fieldname = $fieldname;
    }
    
    public function getAsArray(): array {
        return ['type' => 'IsSet', 'fieldname' => $this->fieldname];
    }
    
    public function getSQLFragment(): string {
        if ($this->type->getStoreLocation() != \Platform\Datarecord\Type::STORE_DATABASE) {
            $this->setNoSQL();
            return true;
        }
        $sql = $this->type->filterIsSetSQL($this->value);
        if ($sql === false) {
            $this->setNoSQL();
            return 'TRUE';
        }
        return $sql;
    }
    
    protected function manualMatch(Datarecord $datarecord, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return $this->type->filterIsSet($datarecord->getRawValue($this->fieldname), $this->value);
    }
    
    public function validate() {
        if (! $this->type) return ['No type attached to condition'];
        return true;
    }
}

