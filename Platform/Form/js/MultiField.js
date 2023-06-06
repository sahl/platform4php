Platform.Form.MultiField = class extends Platform.Form.MultiplierSection {
    
    clear() {
        this.dom_node.find('.platform_form_multiplier_element').not(':first').remove();
        this.dom_node.find('input[type="text"]').val('');
    }
    
    setValue(value) {
        var element = this.dom_node;
         $.each(value, function(key, val) {
             // Can't we do better than keyup?
             element.find('input[type="text"]:last').val(val).trigger('keyup');
         });
    }
}

Platform.Component.bindClass('platform_component_form_multi_field', Platform.Form.MultiField);