Platform.Form.SelectField = class extends Platform.Form.Field {
    
    initialize() {
        super.initialize();
        var component = this;
        this.dom_node.change(function() {
            component.setColourFromSelected();
        });
        this.setColourFromSelected();
    }
    
    addOption(key, value) {
        var html = '<option value="'+key+'"> '+value+'</option>';
        this.dom_node.find('select').append(html);
    }

    clearOptions() {
        this.dom_node.find('select option[class!="heading"]').remove();
    }

    removeOption(key) {
        this.dom_node.find('option[value="'+key+'"]').remove();
    }
    
    clear() {
        this.dom_node.find('option:first-child').prop('selected', true);
        this.setColourFromSelected();
    }
    
    addAllowedOption(option) {
        super.addAllowedOption(option);
        this.dom_node.find('option[value="'+option+'"]').removeClass('platform_hidden_option');
    }
    
    setAllowedOptions(allowed_options) {
        super.setAllowedOptions(allowed_options);
        var component = this;
        if (allowed_options === false) {
            // All is allowed
            this.dom_node.find('option').removeClass('platform_hidden_option');
        }
        if (Array.isArray(allowed_options)) {
            this.dom_node.find('option').addClass('platform_hidden_option');
            allowed_options.forEach(element => component.dom_node.find('option[value="'+element+'"]').removeClass('platform_hidden_option'));
        }
    }
    
    setColourFromSelected() {
        var dom_node = this.dom_node.find('select');
        var selected_element = dom_node.find('option:selected');
        if (selected_element.length == 0) return;
        dom_node.css('color', selected_element.css('color'));
        dom_node.css('background', selected_element.css('background'));
    }

    setValue(value) {
        if (value !== null && this.dom_node.find('option[value="'+value+'"]').length) this.dom_node.find('select').val(value);
        else this.dom_node.find('option:first-child').prop('selected', true);
        this.setColourFromSelected();
    }

    setDisabled(disabled) {
        if (disabled !== false)   disabled = true;
        this.dom_node.find('select').prop('disabled', disabled);
        return true;
    }
    
    isDisabled() {
        return this.dom_node.find('select').is(':disabled');
    }
    
    setReadonly(readonly) {
        if (readonly !== false)   readonly = true;
        this.dom_node.find('select').prop('readonly', readonly);
        return true;
    }
    
    getValue() {
        return this.dom_node.find('select').val();
    }
}

Platform.Component.bindClass('platform_component_select_field', Platform.Form.SelectField);
