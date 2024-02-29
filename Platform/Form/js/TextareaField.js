Platform.Form.TextareaField = class extends Platform.Form.Field {
    
    clear() {
        this.dom_node.find('textarea').val('');
    }

    getValue() {
        return this.dom_node.find('textarea').val();
    }

    setValue(value) {
        this.dom_node.find('textarea').val(value);
    }

    setDisabled(disabled) {
        if (disabled !== false)   disabled = true;
        this.dom_node.find('textarea').prop('disabled', disabled);
        return true;
    }
    
    isDisabled() {
        return this.dom_node.find('textarea').is(':disabled');
    }
    
    setReadonly(readonly) {
        if (readonly !== false)   readonly = true;
        this.dom_node.find('textarea').prop('readonly', readonly);
        return true;
    }    
}

Platform.Component.bindClass('platform_component_textarea_field', Platform.Form.TextareaField);
