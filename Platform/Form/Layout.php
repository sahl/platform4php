<?php
namespace Platform\Form;


class Layout {
    
    /**
     * Group definitions of this layout
     * @var array
     */
    private $groups = [];
    
    /**
     * Add a group to this layout
     * @param float $width Width of group in %
     * @param string $class Name of classes to add to group
     */
    public function addGroup(float $width, string $class) {
        $this->groups[] = ['width' => $width, 'class' => $class];
    }
    
    /**
     * Add a group from a string. The string should be formatted as "width,classes"
     * @param string $group 
     */
    public function addGroupFromString(string $group) {
        $split = explode(',',$group);
        if (count($split) > 2) trigger_error('Invalid string passed as group', E_USER_ERROR);
        if (! isset($split[1])) $split[1] = '';
        $this->addGroup($split[0], $split[1]);
    }
    
    /**
     * Add several groups from an array. Each element is strings formatted as "width,classes"
     * @param array $groups
     */
    public function addGroupsFromArray(array $groups) {
        foreach ($groups as $group) {
            $this->addGroupFromString($group);
        }
    }
    
    /**
     * Get HTML representing a given datarecord in the given layout
     * @param \Platform\Datarecord $datarecord
     * @return string
     */
    public function getHTMLFromDatarecord(\Platform\Datarecord $datarecord, $skip_empty = true) : string {
        $full_definition = $datarecord->getFullDefinition();
        $html = '<div class="form_layout_container">';
        $group_id = 0;
        
        // gather all group widths
        $group_widths = [];
        foreach ($this->groups as $group) {
            $group_widths[] = $group['width'];
        }

        // Generate a section with fields not belonging to the Layout to find out if it exists
        $additional_html = '';
        $fragments = []; $sorter = [];
        foreach ($full_definition as $key => $definition) {
            // Add fields that doesn't belong in a group
            if (! $definition['layout_hide'] && ! $definition['invisible'] && ($definition['layout_group'] == 0 || $definition['layout_group'] > count($this->groups)) && (! $skip_empty || $datarecord->getFullValue($key))) {
                $fragments[] = '<div class="row"><div class="label">'.$definition['label'].'</div><div class="content">'.$datarecord->getFullValue($key).'</div></div>';
                $sorter[] = $definition['layout_priority'] ?: 1000;
            }
        }
        array_multisort($sorter, SORT_ASC, $fragments);
        $additional_html .= implode('', $fragments);
        // If the final section exists add it to the widths, otherwise add a small fraction (to make it the rightmost item if we exactly matched the width
        $group_widths[] = $additional_html ? 100 : 0.001;
        
        // Add the first element to the width
        $current_width = $group_widths[0];
        
        foreach ($this->groups as $i => $group) {
            $group_id++;
            // Add the next element to the width to check if this will overflow the line, leaving this as the final object on the line
            $current_width += $group_widths[$i+1];
            // Add the final element class if the next line will overflow
            if ($current_width > 100) {
                $current_width -= 100;
                $wrap_class = ' rightmost_group';
            } else {
                $wrap_class = '';
            }
            $html .= '<div class="form_layout_group '.$group['class'].$wrap_class.'" style="width: '.$group['width'].'%;">';
            // Add relevant fields
            $fragments = []; $sorter = [];
            foreach ($full_definition as $key => $definition) {
                if (! $definition['layout_hide'] && ! $definition['invisible'] && $definition['layout_group'] == $group_id && (! $skip_empty || $datarecord->getFullValue($key))) {
                    $fragments[] = '<div class="row"><div class="label">'.$definition['label'].'</div><div class="content">'.$datarecord->getFullValue($key).'</div></div>';
                    $sorter[] = $definition['layout_priority'] ?: 1000;
                }
            }
            array_multisort($sorter, SORT_ASC, $fragments);
            $html .= implode('', $fragments);
            $html .= '</div>';
        }
        
        // Add the additional html
        if ($additional_html) {
            $html .= '<div class="form_layout_group rightmost_group" style="width: 100%;">';
            $html .= $additional_html;
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Construct a new layout from the given array. See addGroupsFromArray().
     * @param array $groups
     * @return Layout
     */
    public static function getLayoutFromArray(array $groups) : Layout {
        $layout = new Layout();
        $layout->addGroupsFromArray($groups);
        return $layout;
    }
    
    /**
     * Apply this layout to a form by replacing the form fields
     * @param \Platform\Form\Form $form
     */
    public function apply(\Platform\Form\Form $form) {
        // Extract all fields
        $fields = $form->getAllFields();
        
        // Empty fields
        $form->removeAllFields();
        
        // Add container
        $form->addHTML('<div class="form_layout_container">');
        // Add groups to form
        $group_id = 0;
        foreach ($this->groups as $group) {
            $group_id++;
            $form->addHTML('<div class="form_layout_group '.$group['class'].'" style="width: '.$group['width'].'%;">');
            // Add relevant fields
            foreach ($fields as $field) {
                if ($field->getGroup() == $group_id) $form->addField($field);
            }
            $form->addHTML('</div>');
        }
        $form->addHTML('<div class="form_layout_group" style="width: 100%;">');
        foreach ($fields as $field) {
            // Add fields that didn't belong in a group
            if ($field->getGroup() == 0 || $field->getGroup() > $group_id) $form->addField($field);
        }
        $form->addHTML('</div></div>');
    }
    
}
