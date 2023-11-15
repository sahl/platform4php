<?php
namespace Platform\Datarecord;
/**
 * Type class for passwords
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class PasswordType extends TextType {
    
    /**
     * Filter if a value is greater or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreaterEqual($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterEqualSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is greater than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreater($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is set in regards to this type
     * @param mixed $value Value of this
     * @return bool
     */
    public function filterIsSet($value) {
        return $value !== null;
    }
    
    /**
     * Get SQL to determine if a field is set
     * @return bool
     */
    public function filterIsSetSQL() {
        return $this->name.' IS NOT NULL';
    }
    
    /**
     * Filter if a value is like another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLike($value, $other_value) {
        return $this->filterMatch($value, $other_value);
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLikeSQL($value) {
        return $this->filterMatchSQL($value);
    }
    
    /**
     * Filter if a value is lesser or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesserEqual($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserEqualSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is lesser than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesser($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        return password_verify($other_value, $value) || md5($other_value.\Platform\Platform::getConfiguration('password_salt')) == $value;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is part of some values
     * @param mixed $values Other values
     * @return bool
     */
    public function filterOneOfSQL(array $values) {
        return false;
    }    

    public function getSQLFieldType() : string {
        return 'VARCHAR(256) NOT NULL';
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return $this->getTextValue($value);
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return $value ? '********' : '';
    }
    
    public function getFormField(): \Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\PasswordField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
}

