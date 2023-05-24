Platform.Form.Field = class extends Platform.Component {
    
    addOption(key, value) {
        
    }
    
    clear() {
        
    }
    
    clearError() {
        this.dom_node.find('.platform_form_field').removeClass('platform_form_field_error');
        this.dom_node.find('.platform_field_error_container').html('').slideUp();
    }
    
    isEmpty() {
        return this.getValue() == '';
    }
    
    getValue() {
        return this.dom_node.find('input').val();
    }
    
    removeOption(key) {
        
    }
    
    setError(error_message) {
        this.dom_node.find('.platform_form_field').addClass('platform_form_field_error');
        this.dom_node.find('.platform_field_error_container').html(error_message).slideDown();
    }
    
    setOptions(options) {
        
    }
    
    setRequired(value) {
        
    }
    
    setValue(value) {
        this.dom_node.find('input').val(value);
    }
    
    validate() {
        if (this.dom_node.hasClass('form_required_field') && this.isEmpty()) {
            this.setError(Platform.Translation.forUser('This field is required'));
            return false;
        }
        return true;
    }
}

Platform.Component.BindClass('platform_component_form_field', Platform.Form.Field);