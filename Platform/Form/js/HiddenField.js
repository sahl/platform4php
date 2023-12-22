Platform.Form.HiddenField = class extends Platform.Form.Field {

    /**
     * Check if the field is hidden and therefore shouldn't be evaluated when 
     * posting the form. A "hidden" field shouldn't be considered hidden.
     * @returns {bool}
     */
    isHidden() {
        // A hidden field is never hidden ;)
        return false;
    }
    
}

Platform.Component.bindClass('platform_component_hidden_field', Platform.Form.HiddenField);