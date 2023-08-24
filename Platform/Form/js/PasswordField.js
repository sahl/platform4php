Platform.Form.PasswordField = class extends Platform.Form.Field {
    
    initialize() {
        var component = this;
        this.dom_node.find('input[type="password"]').change(function() {
            component.dom_node.find('input[type="hidden"]').val(1);
            return true;
        });
    }
    
    clear() {
        this.dom_node.find('input[type="password"]').val('');
        this.dom_node.find('input[type="hidden"]').val(0);
    }

    getValue() {
        return this.dom_node.find('input[type="password"]').val();
    }

    setValue(value) {
        this.dom_node.find('input[type="password"]').val(value);
        this.dom_node.find('input[type="hidden"]').val(0);
    }
    
}

Platform.Component.bindClass('platform_component_password_field', Platform.Form.PasswordField);