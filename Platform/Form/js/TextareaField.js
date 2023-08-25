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
    
}

Platform.Component.bindClass('platform_component_textarea_field', Platform.Form.TextareaField);