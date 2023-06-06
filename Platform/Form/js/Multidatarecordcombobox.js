Platform.Form.MultidatarecordCombobox = class extends Platform.Form.MultiplierSection {
    
    clear() {
        this.dom_node.find('.platform_form_multiplier_element').not(':first').remove();
        this.dom_node.find('input[type="hidden"]').val('');
        this.dom_node.find('input[type="text"]').val('');
    }
    
    setValue(value) {
        var element = this.dom_node;
         $.each(value, function(key, val) {
             element.find('input[type="hidden"]:last').val(val.id);
             // Can't we do better than keyup?
             element.find('input[type="text"]:last').val(val.visual).data('validated_value', value.visual).trigger('keyup');
         });
    }
}

Platform.Component.bindClass('platform_component_form_multidatarecordcombobox', Platform.Form.MultidatarecordCombobox);