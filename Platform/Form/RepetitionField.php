<?php
namespace Platform\Form;

use Platform\Utilities\Repetition;
use Platform\Utilities\Time;
use Platform\Utilities\Translation;

class RepetitionField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->addContainerClass('repetition_field');
        $this->value = ['type' => 1, 'interval' => 1, 'metadata' => []];
    }
    
    public function parse($value) : bool {
        if (is_array($value)) {
            // Pack data
            $final_value = [];
            
            foreach ($value as $key => $v) {
                switch ($key) {
                    case 'type':
                    case 'interval':
                        $final_value[$key] = $v;
                        break;
                    case 'radio':
                        break;
                    default:
                        $final_value['metadata'][$key] = $v;
                        break;
                }
            }
            if (count($final_value)) $final_value['metadata'] = [];
            else $final_value = null;
            $this->value = $final_value;
        } else {
            $this->value = ['type' => 1, 'interval' => 1, 'metadata' => []];
        }
        return true;
    }    
    
    public function renderInput() {
        // Extract metadata from value
        $value = $this->value;
        foreach ($this->value['metadata'] as $key => $metadata_value) {
            $value[$key] = $metadata_value;
        }
        
        $type_options = [
            Repetition::REPEAT_DAILY => Translation::translateForUser('day'),
            Repetition::REPEAT_WEEKLY => Translation::translateForUser('week'),
            Repetition::REPEAT_MONTHLY => Translation::translateForUser('month'),
            Repetition::REPEAT_YEARLY => Translation::translateForUser('year'),
        ];
        $occurrence_options = [
            1 => Translation::translateForUser('The first'),
            2 => Translation::translateForUser('The second'),
            3 => Translation::translateForUser('The third'),
            4 => Translation::translateForUser('The fourth'),
            -1 => Translation::translateForUser('The last'),
            -2 => Translation::translateForUser('The second-last'),
        ];
        $monthday_options = [];
        for ($i = 1; $i <= 31; $i++) {
            $monthday_options[$i] = $i.'.';
        }
        $weekday_options = Time::getWeekDaysArray();
        $month_options = Time::getMonthsArray();
        
        
        $type_field = new SelectField('', $this->name.'[type]', ['required' => $this->is_required, 'class' => 'repetition_type', 'options' => $type_options, 'value' => $value['type']]);
        
        $interval_field = new NumberField(Translation::translateForUser('Every'), $this->name.'[interval]', ['required' => true, 'class' => 'repetition_interval', 'value' => $value['interval'] ?: 1]);
        
        $weekdays_field = new MulticheckboxField(Translation::translateForUser('Weekdays'), $this->name.'[weekdays]', ['required' => true, 'class' => 'weekdays', 'value' => $value['weekdays'], 'options' => $weekday_options]);
        
        $months_field = new MulticheckboxField(Translation::translateForUser('In these months'), $this->name.'[months]', ['required' => true, 'class' => 'months', 'value' => $value['months'], 'options' => $month_options]);
        
        $monthday_field = new SelectField(Translation::translateForUser('On the'), $this->name.'[monthday]', ['required' => true, 'class' => 'monthday', 'value' => $value['monthday'], 'options' => $monthday_options]);
        
        $occurrence_field = new SelectField('', $this->name.'[occurrence]', ['required' => true, 'class' => 'occurrence', 'value' => $value['occurrence'], 'options' => $occurrence_options]);
        
        $weekday_field = new SelectField('', $this->name.'[weekday]', ['required' => true, 'class' => 'weekday', 'value' => $value['weekday'], 'options' => $weekday_options]);
        
        $day_field = new SelectField(Translation::translateForUser('On the'), $this->name.'[day]', ['required' => true, 'class' => 'day', 'value' => $value['day'], 'options' => $monthday_options]);
        
        $month_field = new SelectField('', $this->name.'[month]', ['required' => true, 'value' => $value['month'], 'class' => 'month', 'options' => $month_options]);

        echo '<div class="interval_type_container">';
        $interval_field->render();
        echo ' ';
        $type_field->render();
        echo '</div>';
        
        
        echo '<div class="typesection type2">';
        $weekdays_field->render();
        echo '</div>';
        
        echo '<div class="typesection type3">';

        echo '<div class="month_exact_day_container month_day_container">';
        $selected = $this->value['monthday'] ? ' checked' : '';
        echo '<input type="radio" name="'.$this->name.'[radio]" class="month_type_radio"'.$selected.'>';
        $monthday_field->render();
        echo '</div>';
        echo '<div class="month_variable_day_container month_day_container">';
        $selected = $this->value['monthday'] ? '' : ' checked';
        echo '<input type="radio" name="'.$this->name.'[radio]" class="month_type_radio"'.$selected.'>';
        $occurrence_field->render();
        $weekday_field->render();
        echo '</div>';

        $months_field->render();

        echo '</div>';
        
        echo '<div class="typesection type4">';
        echo '<div class="year_container">';
        $day_field->render();
        $month_field->render();
        echo '</div>';
        echo '</div>';
    }
}