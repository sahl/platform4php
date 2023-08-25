Platform.Form.FileField = class extends Platform.Form.Field {
    
    clear() {
        var dom_node = this.dom_node.find('iframe');
        dom_node.prop('src', '/Platform/Form/php/file.php?form_name='+dom_node.closest('form').attr('id')+'&field_name='+dom_node.data('name')+'&file_id=');
    }

    getValue() {
    }

    setValue(value) {
        var dom_node = this.dom_node.find('iframe');
        dom_node.prop('src', '/Platform/Form/php/file.php?form_name='+dom_node.closest('form').attr('id')+'&field_name='+dom_node.data('name')+'&file_id='+value);
   }
    
}

Platform.Component.bindClass('platform_component_file_field', Platform.Form.FileField);