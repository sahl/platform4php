Platform.Form.SelectField = class extends Platform.Form.Field {
    
    clear() {
        this.dom_node.find('option:first-child').prop('selected', true);
    }

    setValue(value) {
        if (value !== null) this.dom_node.find('select').val(value);
        else this.dom_node.find('option:first-child').prop('selected', true);
    }
    
}

Platform.Component.bindClass('platform_component_select_field', Platform.Form.SelectField);