Platform.Form.MultiDatarecordCombobox = class extends Platform.Form.MultiplierSection {
    
    clear() {
        this.dom_node.find('.platform_form_multiplier_element').not(':first').remove();
        this.dom_node.find('input[type="hidden"]').val('');
        this.dom_node.find('input[type="text"]').val('');
    }
    
    setValue(value) {
        this.clear();
        var component = this;
        var element = this.dom_node;
         $.each(value, function(key, val) {
             element.find('.platform_form_multiplier_element:last-child').children().platformComponent().setValue(val);
             component.checkForChanges(element.find('.platform_form_multiplier_element:last-child'));
         });
    }
}

Platform.Component.bindClass('platform_component_form_multi_datarecord_combobox', Platform.Form.MultiDatarecordCombobox);