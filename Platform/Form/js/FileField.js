Platform.Form.FileField = class extends Platform.Form.Field {
    
    clear() {
        var dom_node = this.dom_node.find('iframe');
        dom_node.prop('src', '/Platform/Form/php/file.php?form_name='+dom_node.closest('form').attr('id')+'&field_name='+dom_node.data('name')+'&file_id=');
        this.trigger('change');
    }

    getValue() {
    }
    
    isEmpty() { 
        var name = this.dom_node.attr('data-field_name'); 
        var temp_file = this.dom_node.find('input[name="'+name+'[temp_file]"]').val(); 
        return temp_file === ''; 
    } 

    setValue(value) {
        var dom_node = this.dom_node.find('iframe');
        dom_node.prop('src', '/Platform/Form/php/file.php?form_name='+dom_node.closest('form').attr('id')+'&field_name='+dom_node.data('name')+'&file_id='+value);
        this.trigger('change');
   }
   
   getValue() {
       var values = this.dom_node.find('[type="hidden"]');
       return {
           mimetype: $(values[0]).val(),
           action: $(values[1]).val(),
           filename: $(values[2]).val(),
           temp_file: $(values[3]).val()
       };
   }
    
}

Platform.Component.bindClass('platform_component_file_field', Platform.Form.FileField);