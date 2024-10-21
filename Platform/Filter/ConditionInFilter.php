<?php
namespace Platform\Filter;

use Platform\Datarecord\Datarecord;

class ConditionInFilter extends Condition {
    
    private $other_filter = null;
    
    private $valid_results = false;
    
    public function __construct(string $fieldname, $filter) {
        $this->fieldname = $fieldname;
        if ($filter instanceof Filter) $this->other_filter = $filter;
        elseif (is_array($filter)) $this->other_filter = Filter::getFilterFromArray ($filter);
        else trigger_error('Invalid filter passed to InFilter');
    }
    
    public function getAsArray(): array {
        return ['type' => 'InFilter', 'fieldname' => $this->fieldname, 'filter' => $this->other_filter->getAsArray()];
    }
    
    public function getSQLFragment(): string {
        if (! in_array($this->type->getStoreLocation(), [\Platform\Datarecord\Type::STORE_DATABASE, \Platform\Datarecord\Type::STORE_SUBFIELDS])) {
            $this->setNoSQL();
            return true;
        }
        $sql = $this->type->filterInFilterSQL($this->other_filter);
        if ($sql === false) {
            $this->setNoSQL();
            return 'TRUE';
        }
        return $sql;
    }
    
    protected function manualMatch(Datarecord $datarecord, bool $is_prefiltered) : bool {
        if (! $this->no_sql && $is_prefiltered) return true;
        return $this->type->filterInFilter($datarecord->getRawValue($this->fieldname), $this->other_filter);
    }
    
    public function validate() {
        if (! $this->other_filter instanceof Filter) return ['No filter attached'];
        return true;
    }
    
    
}

