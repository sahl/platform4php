<?php
namespace Platform\Form;
/**
 * Field for showing a dropdown menu
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class SelectField extends Field {
    
    public static $component_class = 'platform_component_select_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/SelectField.js'); 
    }
    
    public function renderInput() {
        $attributes = ['data-fieldclass' => $this->getFieldClass(),
                       'class' => $this->getFieldClasses(),
                       'style' => 'width: '.$this->field_width.'; max-width: '.$this->field_width.';',
                       'name' => $this->name,
                       'id' => $this->getFieldIdForHTML()
                       ];
        foreach ($attributes as $name => $value)    $attributes[$name] = $name . '= "' . $value . '"';
        
        /**
         * The options list can be either a array of title hashed by fieldname, or it can be an array
         * of subarrays hashed by group names, in which case each subarray is title hashed by fieldname
         */
        echo '<select '.implode(' ', $attributes).'>';
        echo   '<option style="background: white; colour:black;" value="" class="heading">'.$this->heading;
        foreach ($this->options as $key => $option) {
            if (is_array($option)) {
                $group = $key;
                if ($group)   echo '<optgroup label="'.$group.'">';
                $options = $option;
                foreach ($options as $key => $option) {
                    $selected = $key == $this->value ? ' selected' : '';
                    $style = 'background: white; color: black;';
                    if (array_key_exists($key, $this->options_colours) && $this->options_colours[$key])
                        $style = 'background: '.$this->options_colours[$key].'; color:'.\Platform\Utilities\Utilities::getContrastColour($this->options_colours[$key]).';';
                    echo '<option style="'.$style.'" value="'.htmlentities($key, ENT_QUOTES).'"'.$selected.'>'.$option;
                }
                if ($group)   echo '</optgroup>';
            } else {
                $selected = $key == $this->value ? ' selected' : '';
                $style = 'background: white; color: black;';
                if (array_key_exists($key, $this->options_colours) && $this->options_colours[$key])
                    $style = 'background: '.$this->options_colours[$key].'; color:'.\Platform\Utilities\Utilities::getContrastColour($this->options_colours[$key]).';';
                echo '<option style="'.$style.'" value="'.htmlentities($key, ENT_QUOTES).'"'.$selected.'>'.$option;
            }
        }
        echo '</select>';
    }
}