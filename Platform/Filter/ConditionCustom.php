<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;
use Platform\Datarecord\Type;

class ConditionCustom extends Condition {
    
    protected $custom_condition = null;

    public function __construct($custom_condition, string $fieldname, $value) {
        $this->fieldname = $fieldname;
        $this->custom_condition = $custom_condition;
        $this->value = $value;
    }
    
    /**
     * Attach a filter to this condition
     * @param Filter $filter
     */
    public function attachFilter(Filter $filter) {
        $this->filter = $filter;
        if ($this->fieldname) {
            $this->type = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
            $this->no_sql = $this->type->getStoreLocation() != Type::STORE_DATABASE;
        }
        if ($this->no_sql) $filter->setFilterAfterSQL();
    }
    
    public function getAsArray(): array {
        return ['type' => 'Custom', 'custom_condition' => $this->custom_condition, 'fieldname' => $this->fieldname, 'value' => $this->value];
    }
    
    public function getSQLFragment(): string {
        if ($this->type->getStoreLocation() != \Platform\Datarecord\Type::STORE_DATABASE) {
            $this->setNoSQL();
            return true;
        }
        $sql = $this->type->filterCustomSQL($this->custom_condition, $this->value);
        if ($sql === false) {
            $this->setNoSQL();
            return 'TRUE';
        }
        return $sql;
    }
    
    protected function manualMatch(Datarecord $datarecord, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return $this->type->filterCustom($this->custom_condition, $datarecord->getRawValue($this->fieldname), $this->value);
    }
    
    public function validate() {
        return true;
    }
}