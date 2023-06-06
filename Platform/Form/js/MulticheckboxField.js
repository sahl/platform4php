Platform.Form.MulticheckboxField = class extends Platform.Form.Field {
    
    clear() {
        this.dom_node.find('input[type="checkbox"]').prop('checked', false);
    }

    getValue() {
        var result = [];
        $(this.dom_node).find('input[type="checkbox"]:checked').each(function() {
            result.push($(this).val());
        })
        return result;
    }

    setValue(value) {
        this.clear();
        this.checkWithValues(value);
    }
    
    checkWithValues(ids) {
        var dom_node = this.dom_node;
        $.each(ids, function(key, value) {
            dom_node.find('input[value="'+value+'"]').prop('checked', true);
        });
    }
    
    
}

Platform.Component.bindClass('platform_component_multicheckbox_field', Platform.Form.MulticheckboxField);