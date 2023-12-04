Platform.Form.SelectField = class extends Platform.Form.Field {
    
    addOption(key, value) {
        var html = '<option value="'+key+'"> '+value+'</option>';
        this.dom_node.find('select').append(html);
    }

    clearOptions() {
        this.dom_node.find('select').html('');
    }

    removeOption(key) {
        this.dom_node.find('option[value="'+key+'"]').remove();
    }
    
    clear() {
        this.dom_node.find('option:first-child').prop('selected', true);
    }

    setValue(value) {
        if (value !== null) this.dom_node.find('select').val(value);
        else this.dom_node.find('option:first-child').prop('selected', true);
    }
    
    getValue() {
        return this.dom_node.find('select').val();
    }
    
    
}

Platform.Component.bindClass('platform_component_select_field', Platform.Form.SelectField);