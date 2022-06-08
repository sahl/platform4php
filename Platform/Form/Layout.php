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
     * @param \Platform\Form $form
     */
    public function apply(\Platform\Form $form) {
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
