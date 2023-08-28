$(function() {
    // Focus first form field on page load
    $('.platform_form input[type!="hidden"][type!="checkbox"]').first().focus();
    // Focus on auto-focus field
    $('.platform_autofocus').first().focus();
});

Platform.Form = class extends Platform.Component {
    
    initialize() {
        var component = this;
        var dom_node = this.dom_node;
        this.dom_node.find('form').submit(function(e) {
            if (component.validate()) {
                return true;
            }
            e.stopImmediatePropagation();
            return false;
        })

        /*
        
        // Submit on enter for some fields
        $('.platform_form_field', this.dom_node).not('textarea').keypress(function(e) {
            if (e.keyCode == 13) {
                e.stopImmediatePropagation();
                $(this).closest('form').submit();
                return false;
            }
        });
        */
        // Autosubmit
        $(this.dom_node).on('component_ready', function() {
            // We cannot submit table control forms before the table is ready, so those submit is handled by the table
            if (! component.dom_node.is('.platform_table_control_form')) component.dom_node.find('form.platform_form_auto_submit').submit();
        });
    }
    
    attachErrors(errors) {
        var component = this;
        $.each(errors, function(fieldname, error_text) {
            if (fieldname == '__global') {
                component.dom_node.find('.platform_form_global_error_container').html('<ul><li>'+error_text.join('<li>')+'</ul>').show();
            } else {
                var field_component = component.dom_node.find('#'+component.dom_node.data('componentproperties')['form_id']+'_'+Platform.escapeSelector(fieldname)+'_component').platformComponent();
                if (! field_component) return true;
                field_component.setError(error_text);
            }
        })
    }
    
    attachValues(values) {
        var component = this;
        $.each(values, function(fieldname, value) {
            var field_component = component.dom_node.find('#'+component.dom_node.data('componentproperties')['form_id']+'_'+Platform.escapeSelector(fieldname)+'_component').platformComponent();
            if (! field_component) return true;
            field_component.clear();
            field_component.setValue(value);
        })
    }
    
    clear() {
        this.dom_node.find('.platform_form_global_error_container').html('').hide();
        this.dom_node.find('.platform_form_field').each(function() {
            $(this).platformComponent().clear();
            $(this).platformComponent().clearError();
        });
    }
    
    submit() {
        this.dom_node.find('form').submit();
    }
    
    getFieldByName(name) {
        return this.dom_node.find('#'+this.dom_node.children('form').prop('id')+'_'+Platform.escapeSelector(name)+'_component').platformComponent();
    }
    
    validate() {
        var allowsubmit = true;
        var form_node = this.dom_node.find('form');
        
        this.dom_node.find('form').children('.platform_form_field').each(function() {
            var field_component = $(this).platformComponent();
            if (! field_component.validate()) {
                allowsubmit = false;
            }
        });

        // Gather hidden fields
        var hiddenfields = [];

        $('input,select,textarea', form_node).filter(':hidden,:disabled').each(function() {
            // We accept hidden fields
            if ($(this).is('[type="hidden"]')) return true;
            // We accept hidden texteditors
            if ($(this).is('.texteditor')) return true;
            var name = $(this).prop('name');
            name = name.replace('[]', '');
            hiddenfields.push(name);
        });
        if (hiddenfields.length) form_node.find('[name="form_hiddenfields"]').val(hiddenfields.join(' '));

        if (allowsubmit) {
            // Check if we should save form values
            if (this.dom_node.data('save_on_submit')) {
                $.post('/Platform/Form/php/save_form_values.php', {destination: this.dom_node.data('save_on_submit'), formid: form_node.prop('id'), formdata: form_node.serialize()});
            }
        }
        return allowsubmit;
    }
}

Platform.Component.bindClass('platform_component_form', Platform.Form);