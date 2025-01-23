Platform.Form.Field = class extends Platform.Component {
    
    /**
     * Initialize the field
     */
    initialize() {
    }
    
    /**
     * Add an option to the field
     * @param string key Key to option
     * @param string value Text value to option
     */
    addOption(key, value) {
    }
    
    /**
     * Clear the field
     */
    clear() {
        this.dom_node.find('input').val('');
    }
    
    /**
     * Clear any error message from the field
     */
    clearError() {
        this.dom_node.find('.platform_form_field').removeClass('platform_form_field_error');
        this.dom_node.find('.platform_field_error_container').html('').slideUp();
    }
    
    /**
     * Clear all options from the field
     */
    clearOptions() {
    }
    
    /**
     * Check if the field is to be considered empty
     * @returns {bool}
     */
    isEmpty() {
        return this.getValue() == '';
    }
    
    /**
     * Check if the field is hidden and therefore shouldn't be evaluated when 
     * posting the form. A "hidden" field shouldn't be considered hidden.
     * @returns {bool}
     */
    isHidden() {
        return ! this.dom_node.is(':visible') && ! this.dom_node.is('.platform_include_in_post');
    }
    
    /**
     * Get the name of this field
     * @returns {string}
     */
    getName() {
        return this.dom_node.data('field_name');
    }
    
    /**
     * Get the value of this field
     * @returns The value
     */
    getValue() {
        return this.dom_node.find('input').val();
    }
    
    /**
     * Remove an option from this field
     * @param {string} key Key of option
     */
    removeOption(key) {
        
    }
    
    /**
     * Add an option as allowed
     * @param {mixed} option
     */
    addAllowedOption(option) {
        
    }
    
    /**
     * Set which options are allowed
     * @param {mixed} allowed_options
     */
    setAllowedOptions(allowed_options) {
        if (Array.isArray(allowed_options)) {
            if (! allowed_options.includes(this.getValue())) this.setValue('');
        }
    }

    /**
     * Get the container for the error message
     * @returns {jQuery}
     */
    getErrorContainer() {
        var result = $();
        var dom_node = this.dom_node;
        // As there can be nested fields, we need to ensure we don't find an error container in another field
        this.dom_node.find('.platform_field_error_container').each(function() {
            if (! $(this).closest('.platform_form_field_component').is(dom_node)) return true;
            result = $(this);
            return false;
        });
        return result;
    }
    
    /**
     * Trigger and show an error message in the field
     * @param {string} error_message The error message to show
     */
    setError(error_message) {
        this.dom_node.find('.platform_form_field').addClass('platform_form_field_error');
        this.getErrorContainer().html(error_message).slideDown();
    }
    
    /**
     * Set options of this field
     * @param {array} options
     */
    setOptions(options) {
        var field = this;
        var existing_value = this.getValue();
        this.clearOptions();
        $.each(options, function(key, value) {
            field.addOption(key, value);
        })
        this.setValue(existing_value);
    }
    
    /**
     * Set if this field is required or not
     * @param {bool} is_required
     */
    setRequired(is_required) {
        var span = this.dom_node.find('.platform_field_label_container label span');
        
        if (span.length == 0) { // create the span for the *
            this.dom_node.find('.platform_field_label_container label').append(' <span style="color: red; font-size: 0.8em;"></span>');
            span = this.dom_node.find('.platform_field_label_container label span');
        }
        
        if (is_required) {
            this.dom_node.addClass('form_required_field');
            span.text('*');
        } else {
            this.dom_node.removeClass('form_required_field');
            span.text('');
        }
    }
    
    /**
     * Check if a field is required
     * @returns bool
     */
    isRequired() {
        return this.dom_node.is('.form_required_field');
    }
    
    /**
     * Set the value of this field
     * @param value
     */
    setValue(value) {
        this.addAllowedOption(value);
        this.dom_node.find('input').val(value);
    }
    
    /**
     * Validate if this field is filled correctly
     * @returns {bool} True if field validates.
     */
    validate() {
        if (this.dom_node.hasClass('form_required_field') && this.isEmpty()) {
            this.setError(Platform.Translation.forUser('This field is required'));
            return false;
        }
        return true;
    }
    
    /**
     * Show the field + container
     */
    show() {
        this.toggle(true);
    }
    
    /**
     * Hide the field + container
     */
    hide() {
        this.toggle(false);
    }
    
    /**
     * Show/hide the field + container
     * @param bool show
     */
    toggle(show) {
        this.dom_node.toggle(show);
    }
    
    /**
     * Disable/enable the field
     * @param bool disabled
     * @return bool Return false if the field doesn't support disable
     */
    setDisabled(disabled) {
        if (disabled !== false)   disabled = true;
        this.dom_node.find('input').prop('disabled', disabled);
        return true;
    }
    
    /**
     * Check if the field is disabled
     * @return bool
     */
    isDisabled() {
        return this.dom_node.find('input').is(':disabled');
    }
    
    /**
     * Make the field readonly or readable; notice that readonly and disabled are different things!
     * @param bool readonly
     * @return bool Return false if the field doesn't support readonly
     */
    setReadonly(readonly) {
        if (readonly !== false)   readonly = true;
        this.dom_node.find('input').prop('readonly', readonly);
        return true;
    }
    
    /**
     * Set the label of the field
     * @param string text
     */
    setLabel(text) {
        var label = this.dom_node.find('.platform_field_label_container label');
        // make sure we keep the <span> for the required-ness
        var is_required = this.isRequired();
        label.text(text);
        this.setRequired(is_required);
    }
}

Platform.Component.bindClass('platform_component_field', Platform.Form.Field);