Platform.Form.CheckboxField = class extends Platform.Form.Field {
    
    clear() {
        this.dom_node.find('input[type="checkbox"]').prop('checked', false);
    }

    getValue() {
        return this.dom_node.find('input[type="checkbox"]').prop('checked') ? 1 : 0;
    }

    setValue(value) {
        this.dom_node.find('input[type="checkbox"]').prop('checked', value == 1);
    }
    
    isEmpty() {
        return ! this.dom_node.find('input[type="checkbox"]').is(':checked');
    }
    
}

Platform.Component.bindClass('platform_component_checkbox_field', Platform.Form.CheckboxField);