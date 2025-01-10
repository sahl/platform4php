Platform.Form.MultiCheckboxField = class extends Platform.Form.Field {
    
    addOption(key, value) {
        var html = '<div class="platform_multicheck_option"><input style="vertical-align: -1px; margin: 0px;" type="checkbox" name="'+this.dom_node.find('.platform_multicheck_container').data('realname')+'[]" value="'+key+'"> '+value+'</div>';
        this.dom_node.find('.platform_multicheck_container').append(html);
    }
    
    clear() {
        this.dom_node.find('input[type="checkbox"]').prop('checked', false);
    }
    
    clearOptions() {
        this.dom_node.find('.platform_multicheck_container').html('');
    }
    
    removeOption(key) {
        this.dom_node.find('input[value="'+key+'"]').parent().remove();
    }
    
    addAllowedOption(option) {
        super.addAllowedOption(option);
        this.dom_node.find('input[value="'+option+'"]').closest('.platform_multicheck_option').removeClass('platform_hidden_option');
    }
    
    setAllowedOptions(allowed_options) {
        var component = this;
        if (allowed_options === false) {
            // All is allowed
            this.dom_node.find('.platform_multicheck_option').removeClass('platform_hidden_option');
        }
        if (Array.isArray(allowed_options)) {
            this.dom_node.find('.platform_multicheck_option').addClass('platform_hidden_option');
            allowed_options.forEach(element => component.dom_node.find('input[value="'+element+'"]').closest('.platform_multicheck_option').removeClass('platform_hidden_option'));
            this.dom_node.find('.platform_hidden_option').find('input').prop('checked', false);
        }
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

Platform.Component.bindClass('platform_component_multi_checkbox_field', Platform.Form.MultiCheckboxField);