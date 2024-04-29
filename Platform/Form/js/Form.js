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

        // Autosubmit
        $(this.dom_node).on('component_ready', function() {
            // We cannot submit table control forms before the table is ready, so those submit is handled by the table
            if (! component.dom_node.is('.platform_table_control_form')) component.dom_node.find('form.platform_form_auto_submit').submit();
        });
    }
    
    /**
     * Attach one or more errors to this form
     * @param {object} errors Error texts hashed by the field names
     */
    attachErrors(errors) {
        var component = this;
        $.each(errors, function(fieldname, error_text) {
            if (fieldname == '__global') {
                component.dom_node.find('.platform_form_global_error_container').html('<ul><li>'+error_text.join('<li>')+'</ul>').show();
            } else {
                var field_component = component.getFieldByName(fieldname);
                if (! field_component) return true;
                field_component.setError(error_text);
            }
        })
    }
    
    /**
     * Set values a bunch of fields in the form
     * @param {object} values Field values hashed by the field names
     */
    attachValues(values) {
        var component = this;
        $.each(values, function(fieldname, value) {
            var field_component = component.getFieldByName(fieldname);
            if (! field_component) return true;
            field_component.clear();
            field_component.clearError();
            field_component.setValue(value);
        });
        component.dom_node.find('form').trigger('values_changed');
    }
    
    /**
     * Clear all fields in the form, and clear all errors
     */
    clear() {
        this.dom_node.find('.platform_form_global_error_container').html('').hide();
        $.each(this.getFields(), function(idx, component) {
            component.clear();
            component.clearError();
        });
    }
    
    /**
     * Set an event for the form
     * @param {string} event
     */
    setEvent(event) {
        this.dom_node.find('input[name="form_event"]').val(event);
    }
    
    /**
     * Submit the form
     * @param {string} event Optional event
     */
    submit(event) {
        if (event) this.setEvent(event);
        this.dom_node.find('form').submit();
    }
    
    /**
     * Lookup a field by its name
     * @param string name
     * @returns Component Returns null if not found
     */
    getFieldByName(name) {
        return this.dom_node.find('#'+this.dom_node.data('componentproperties')['form_id']+'_'+Platform.escapeSelector(name)+'_component').platformComponent();
    }
    
    /**
     * Get the values of all fields in the form that validate, hidden fields are not returned
     * @returns object Values hashed by their field names
     */
    getValues() {
        var values = {};
        $.each(this.getFields(), function(idx, component) {
            if (!component.isHidden() && component.validate() && !component.isDisabled()) {
                var name = component.getName();
                var value = component.getValue();
                values[name] = value;
            }
        });
        return values;
    }
    
    getFields() {
        return Platform.Form.getFieldsFromNode(this.dom_node);
    }
    
    /**
     * Get all form fields in part of the page, if multiple fields have the same name then only the last will be returned
     * @param {jQuery} selector Specifies which part of the page to scan
     * @returns {object} Components hashed by their field names
     */
    static getFieldsFromNode(selector) {
        var fields = {};
        
        $(selector).closestChildren('.platform_form_field_component').each(function() {
            var component = $(this).platformComponent();
            if (component) {
                var name = component.getName();
                fields[name] = component;
            }
        });
        return fields;
    }
    
    /**
     * Check if all fields have valid values; hidden fields and disabled fields are not checked
     * If no errors are encountered, and the form is set to "save on submit" then the form values are sent to the server 
     * @returns bool
     */
    validate() {
        var allow_submit = true;
        
        // Gather hidden fields
        var hidden_fields = [];
    
        // Validate form by validating all fields not considered hidden
        $.each(this.getFields(), function(idx, field_component) {
            if (field_component.isHidden()) {
                var name = field_component.getName();
                name = name.replace('[]', '');
                hidden_fields.push(name);
                return true;
            }
            if (!field_component.isDisabled() && ! field_component.validate()) {
                allow_submit = false;
            }
            return true;
        });

        if (hidden_fields.length) form_node.find('[name="form_hiddenfields"]').val(hidden_fields.join(' '));

        if (allow_submit) {
            // Check if we should save form values
            if (this.dom_node.data('save_on_submit')) {
                $.post('/Platform/Form/php/save_form_values.php', {destination: this.dom_node.data('save_on_submit'), formid: form_node.prop('id'), formdata: form_node.serialize()});
            }
        }
        return allow_submit;
    }
}

Platform.Component.bindClass('platform_component_form', Platform.Form);