<?php
namespace Platform\Datarecord;
/**
 * Type class for float number
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class FloatType extends IntegerType {
    
    protected $minimum_decimals = 0;
    
    protected $maximum_decimals = 100;
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        if (isset($options['minimum_decimals'])) {
            $this->minimum_decimals = (int)$options['minimum_decimals'];
            unset($options['minimum_decimals']);
        }
        if (isset($options['maximum_decimals'])) {
            $this->maximum_decimals = (int)$options['maximum_decimals'];
            unset($options['maximum_decimals']);
        }
        parent::__construct($name, $title, $options);
    }
    

    public function getBaseFormField() : ?\Platform\Form\Field {
        if ($this->is_formatted) return \Platform\Form\FormattedNumberField::Field($this->title, $this->name, $this->getFormFieldOptions());
        else return \Platform\Form\NumberField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    public function getFieldForDatabase($value) : string {
        if ($value === null || $value === '') return 'NULL';
        return (string)$value;
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        if ($this->is_formatted) {
            // Find number of decimals
            $parts = explode('.', (string)$value);
            $number_of_decimals = strlen($parts[1]);
            $decimals_to_display = max(min($number_of_decimals, $this->maximum_decimals), $this->minimum_decimals);
            return \Platform\Utilities\NumberFormat::getFormattedNumber($value, $decimals_to_display, true);
        }
        else return (string)$value;
    }
    
    public function getFormFieldOptions(): array {
        $result = parent::getFormFieldOptions();
        if (! $this->is_formatted) $result['allow_decimal'] = true;
        if ($this->minimum_decimals && $this->is_formatted) $result['minimum_decimals'] = $this->minimum_decimals;
        if ($this->maximum_decimals && $this->is_formatted) $result['maximum_decimals'] = $this->maximum_decimals;
        return $result;
    }
    
    /**
     * Get the minimum number of decimals to use in this number
     * @return int
     */
    public function getMinimumDecimals() : int {
        return $this->minimum_decimals;
    }
    
    /**
     * Get the maximum number of decimals to use in this number
     * @return int
     */
    public function getMaximumDecimals() : int {
        return $this->maximum_decimals;
    }
    
    /**
     * Get a sorter for the Table component
     * @return array
     */
    public function getTableSorter() : array {
        return ['sorter' => 'number'];
    }

    public function getSQLFieldType() : string {
        return 'DOUBLE';
    }
    
    public function parseDatabaseValue($value) {
        if ($value === null) return null;
        return (float)$value;
    }    
    
    public function parseValue($value, $existing_value = null) {
        if ($value === null) return null;
        return (float)$value;
    }    
    
    /**
     * Set the minimum number of decimals that should display when showing this number
     * @param int $minimum_decimals
     */
    public function setMinimumDecimals(int $minimum_decimals) {
        $this->minimum_decimals = $minimum_decimals;
    }
    
    /**
     * Set the maximum number of decimals that should display when showing this number
     * @param int $maximum_decimals
     */
    public function setMaximumDecimals(int $maximum_decimals) {
        $this->maximum_decimals = $maximum_decimals;
    }

    public function validateValue($value) {
        if ($value !== null && !is_numeric($value)) return false;
        return true;
    }
    
}

