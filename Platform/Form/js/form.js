$(function() {
    // Focus first form field on page load
    $('.platform_form input[type!="hidden"][type!="checkbox"]').first().focus();
    // Focus on auto-focus field
    $('.platform_autofocus').first().focus();
});

addPlatformComponentHandlerFunction('form', function(item) {
    $('form', item).submit(function(e) {
        var allowsubmit = true;
        
        // Clear all previous errors
        $('.platform_form_global_error_container', $(this)).hide();
        $('.platform_form_field,.platform_component_fieldcomponent', $(this)).clearError();
        
        // Hide last item of multipliers as these should always be empty and not submitted or validated.
        $('.platform_form_multiplier_element', $(this)).each(function() {
            if ($(this).is(':last-child')) $(this).hide();
        });        

        // Check required fields
        $('input.form_required_field,select.form_required_field', $(this)).each(function() {
            if ($(this).val().length == 0 && $(this).is(':visible') && ! $(this).is(':disabled')) {
                $(this).setError('This is a required field');
                allowsubmit = false;
            }
            return true;
        });
        $('.multi_checkbox_container.form_required_field:visible', this).each(function() {
            if (! $(this).find('input[type="checkbox"]:checked').length) {
                $(this).setError('This is a required field');
                allowsubmit = false;
            }
        })
        
        // Validate component fields
        $('.platform_component_fieldcomponent').each(function() {
            $(this).trigger('validate');
            if ($(this).hasClass('platform_form_field_error')) allowsubmit = false;
        })

        // Gather hidden fields
        var hiddenfields = [];

        $('.platform_form_field:hidden,.platform_form_field:disabled', $(this)).each(function() {
            // We accept hidden fields
            if ($(this).is('[type="hidden"]')) return true;
            // We accept hidden texteditors
            if ($(this).is('.texteditor')) return true;
            var name = $(this).prop('name');
            if ($(this).data('realname')) {
                name = $(this).data('realname');
            }
            hiddenfields.push(name);
        });
        if (hiddenfields.length) $(this).find('[name="form_hiddenfields"]').val(hiddenfields.join(' '));
        
        if (! allowsubmit) {
            e.stopImmediatePropagation();
        } else {
            // Check if we should save form values
            if ($(this).data('save_on_submit')) {
                $.post('/Platform/Form/php/save_form_values.php', {destination: $(this).data('save_on_submit'), formid: $(this).prop('id'), formdata: $(this).serialize()});
            }
        }

         // Show multipliers again
        $('.platform_form_multiplier_element', $(this)).show();
        return allowsubmit;
     });
     
     // Submit on enter for some fields
     $('.platform_form_field', item).not('textarea').keypress(function(e) {
         if (e.keyCode == 13) {
             e.stopImmediatePropagation();
             $(this).closest('form').submit();
             return false;
         }
     });
     
    // Add currency handler
    $('.currency_currency,.currency_foreignvalue', item).change(function() {
        var component = $(this);
        item.componentIO({
            event: 'currency_lookup',
            foreignvalue: $(this).parent().find('.currency_foreignvalue').val(),
            currency: $(this).parent().find('.currency_currency').val()
        }, function(data) {
            if (data.status == 1) component.parent().find('.currency_localvalue').val(data.localvalue);
        })
    })
    $('.currency_currency').change(function() {
        if ($(this).val() == '') $(this).parent().find('.currency_foreignvalue').val('');
    })

    // Clear errors when changing fields
     $('.platform_form_field',item).change(function() {
         $(this).clearError();
     });
          
    // Indicate on password-field when it is updated.
    $('.platform-password',item).change(function() {
        $(this).closest('.platform_form_field_container').find('input[type="hidden"]').val(1);
        return true;
    });

    // Autosize required textareas
    autosize($('textarea.autosize', item));

    // Autosubmit
    $(item).on('component_ready', function() {
        // We cannot submit table control forms before the table is ready, so those submit is handled by the table
        if (! $(this).is('.platform_table_control_form')) $(this).find('form.platform_form_auto_submit').submit();
    });
});


$.fn.setError = function(text) {
    this.addClass('platform_form_field_error').closest('.platform_form_field_container').find('.platform_field_error_container').html(text).slideDown();
    return this;
}

$.fn.clearError = function() {
    this.filter('.platform_form_field_error').removeClass('platform_form_field_error').closest('.platform_form_field_container').find('.platform_field_error_container').slideUp();
    return this;
}

$.fn.clearForm = function() {
    this.find('input[type!="hidden"][type!="checkbox"][type!="submit"][type!="button"],input[type="hidden"][name!="form_event"][name!="form_name"]').not('.platform_dont_clear').val('');
    this.find('textarea').not('.platform_dont_clear').val('');
    this.find('textarea.texteditor').summernote('reset');
    this.find('[type="checkbox"]').not('.platform_dont_clear').prop('checked', false);
    this.find('select').not('.platform_dont_clear').find('option:first-child').prop('selected', true);
    this.find('.platform_form_multiplier').each(function() {
        $(this).find('.platform_form_multiplier_element:not(:first-child)').remove();
    });
    this.find('.platform_form_field_error').clearError();
    this.find('iframe').each(function() {
        $(this).prop('src', $(this).prop('src'));
    })
    this.find('.platform_component_fieldcomponent').trigger('reset');
    this.trigger('dataloaded');
    return this;
}

$.fn.attachErrors = function(errors) {
    var form = this;
    $.each(errors, function(form_id, error_message) {
        if (form_id == '__global') {
            $('.platform_form_global_error_container', form).html('<ul><li>'+error_message.join('<li>')+'</ul>').show();
        } else {
            form_id = form_id.replace(/\[/g,'\\[').replace(/\]/g,'\\]');
            $('#'+form_id, form).setError(error_message);
        }
    })
    
}

$.fn.attachValues = function(values) {
    var form = $(this).closest('form');    
    $.each(values, function(key, value) {
        // Get field by figuring out the ID
        var field = $('#'+form.prop('id')+'_'+key);
        // Skip if there wasn't such a field
        if (field.length < 1) return true;
        // Assign value
        Platform.Form.setValue(field, value);
        return true;
        
    })

    this.trigger('dataloaded');
    
}

$.fn.loadValues = function(script, parameters = {}, onload = null) {
    var element = this;
    $.post(script, parameters, function(data) {
        if (data.status == 0) {
            warningDialog('Error loading data', data.errormessage);
        } else {
            $(element).attachValues(data.data);
            element.trigger('dataloaded');
            if (typeof onload == 'function') {
                onload();
            }
        }
    }, 'json');
    return this;
}

function add_errors_to_form(form, errors) {
    $.each(errors, function(form_id, error_message) {
        form_id = form_id.replace(/\[/g,'\\[').replace(/\]/g,'\\]');
        $('#'+form_id, form).setError(error_message);
    })
}


Platform.Form = {
    
    /**
     * Add an option to a field which supports this
     * @param {jQuery} field_selector
     * @param {Object} option
     */
    addOption: function(field_selector, option) {
        
    },

    /**
     * Clear the value from a field
     * @param {jQuery} field_selector
     */
    clearValue: function(field_selector) {
        field_selector.each(function() {
            // Skip if not platform-field
            if (! $(this).is('.platform_form_field')) return true;
            var fieldtype = $(this).data('fieldclass');
            switch (fieldtype) {
                case 'CheckboxField':
                    $(this).prop('checked', false);
                    break;
                case 'TexteditorField':
                    $(this).summernote('reset');
                    break;
                case 'CurrencyField':
                    var container = $(this).parent();
                    container.find('.currency_localvalue').val('');
                    container.find('.currency_currency').val('');
                    container.find('.currency_foreignvalue').val('');
                    break;
                case 'SelectField':
                    $(this).find('option:first-child').prop('selected', true);
                    break;
                case 'DatarecordcomboboxField':
                case 'IndexedComboboxField':
                    var container = $(this).parent();
                    $(this).val('').data('validated_value', '');
                    container.find('input[type="hidden"]').val('');
                    break;
                case 'MulticheckboxField':
                    $(this).find('input[type="checkbox"]').prop('checked', false);
                    break;
                case 'MultidatarecordcomboboxField':
                    $(this).find('.platform_form_multiplier_element').not(':first').remove();
                    $(this).find('input[type="hidden"]').val('');
                    $(this).find('input[type="text"]').val('');
                    break;
                case 'MultiplierSection':
                    $(this).find('.platform_form_multiplier_element').not(':first').remove();
                    $(this).find('.platform_form_field').each(function() {
                        Platform.Form.clearValue($(this));
                    })
                    break;
                case 'MultiField':
                    $(this).find('.platform_form_multiplier_element').not(':first').remove();
                    $(this).find('input[type="text"]').val('');
                    break;
                case 'FileField':
                    var dom_node = $(this);
                    dom_node.prop('src', '/Platform/Form/php/file.php?form_name='+dom_node.closest('form').attr('id')+'&field_name='+dom_node.data('name')+'&file_id=');
                    break;
                case 'RepetitionField':
                    var dom_node = $(this);
                    dom_node.find('.repetition_interval').val(1);
                    dom_node.find('.repetition_type').val('').trigger('change');
                    break;
                default:
                    $(this).val('');
                    break;
            }
        })
    },

    /**
     * Get the value from a field
     * @param {jQuery} field_selector
     * @returns {mixed}
     */
    getValue: function(field_selector) {
        // Skip if not platform-field
        if (! $(field_selector).is('.platform_form_field')) return true;
        var fieldtype = $(field_selector).data('fieldclass');
        switch (fieldtype) {
            case 'CheckboxField':
                return $(field_selector).is(':checked');
            case 'TexteditorField':
                return $(field_selector).summernote('code');
            case 'CurrencyField':
                var container = $(field_selector).parent();
                return {
                    localvalue: container.find('.currency_localvalue').val(),
                    currency: container.find('.currency_currency').val(),
                    foreignvalue: container.find('.currency_foreignvalue').val()
                }
            case 'DatarecordcomboboxField':
            case 'IndexedComboboxField':
                var container = $(field_selector).parent();
                return {
                    id: container.find('input[type="hidden"]').val(),
                    visual: $(field_selector).val(),
                }
            case 'MulticheckboxField':
                var result = [];
                $(field_selector).find('input[type="checkbox"]:checked').each(function() {
                    result.push($(this).val());
                })
                return result;
            case 'MultidatarecordcomboboxField':
                var result = [];
                $(field_selector).find('.platform_form_multiplier_element').each(function() {
                    result.push({
                        id: $(this).find('input[type="hidden"]').val(),
                        visual: $(this).find('input[type="text"]').val()
                    });
                });
                return result;
            case 'MultiplierSection':
                var result = [];
                $(field_selector).find('.platform_form_multiplier_element').each(function() {
                    var inner_result = [];
                    $(field_selector).find('.platform_form_field').each(function() {
                        inner_result.push(Platform.Form.getValue($(this)));
                    })
                    result.push(inner_result);
                });
                return result;
            case 'MultiField':
                var result = [];
                $(field_selector).find('input[type="text"]').each(function() {
                    result.push($(field_selector).val());
                })
                return result;
            case 'FileField':
                // Pending
                return [];
                break;
            case 'RepetitionField':
                // Pending
                return [];
            default:
                return $(field_selector).val();
        }
    },
    
    /**
     * Removes an option from a field which supports this
     * @param {jQuery} field_selector
     * @param {Object} option
     */
    removeOption: function(field_selector, option) {
        
    },
    
    /**
     * Set the value of a field.
     * @param {jQuery} field_selector
     * @param {Object} value
     */
    setValue: function(field_selector, value) {
        field_selector.each(function() {
            // Skip if not platform-field
            if (! $(this).is('.platform_form_field')) return true;
            var fieldtype = $(this).data('fieldclass');
            switch (fieldtype) {
                case 'CheckboxField':
                    $(this).prop('checked', value == 1);
                    break;
                case 'TexteditorField':
                    $(this).summernote('reset');
                    $(this).summernote('code', value);
                    break;
                case 'CurrencyField':
                    var container = $(this).parent();
                    container.find('.currency_localvalue').val(value.localvalue);
                    container.find('.currency_currency').val(value.currency);
                    container.find('.currency_foreignvalue').val(value.foreignvalue);
                    break;
                case 'SelectField':
                    if (value !== null) $(this).val(value);
                    else $(this).find('option:first-child').prop('selected', true);
                    break;
                case 'DatarecordcomboboxField':
                case 'IndexedComboboxField':
                    var container = $(this).parent();
                    $(this).val(value.visual).data('validated_value', value.visual);
                    container.find('input[type="hidden"]').val(value.id);
                    break;
                case 'MulticheckboxField':
                    checkWithId($(this), value);
                    break;
                case 'MultidatarecordcomboboxField':
                    var element = $(this);
                    $.each(value, function(key, val) {
                        element.find('input[type="hidden"]:last').val(val.id);
                        // Can't we do better than keyup?
                        element.find('input[type="text"]:last').val(val.visual).data('validated_value', value.visual).trigger('keyup');
                    });
                    break;
                case 'MultiplierSection':
                    var i = 0;
                    var base_id = $(this).prop('id');
                    var element = $(this);
                    $.each(value, function(dummy, val) {
                        // This is each section
                        var insert_node = element.children('.platform_form_multiplier_element:last');
                        $.each(val, function(inner_key, inner_val) {
                            var inner_id = base_id+'['+(i)+']['+inner_key+']';
                            inner_id = inner_id.replace(/\[/g,"\\[").replace(/\]/g,"\\]");
                            Platform.Form.setValue($('#'+inner_id), inner_val);
                        });
                        // Can't we do better than keyup?
                        insert_node.find('input[type!="checkbox"],textarea').trigger('keyup');
                        i++;
                    });
                    break;
                case 'MultiField':
                    var element = $(this);
                    $.each(value, function(key, val) {
                        // Can't we do better than keyup?
                        element.find('input[type="text"]:last').val(val).trigger('keyup');
                    });
                    break;
                case 'FileField':
                    var dom_node = $(this);
                    dom_node.prop('src', '/Platform/Form/php/file.php?form_name='+dom_node.closest('form').attr('id')+'&field_name='+dom_node.data('name')+'&file_id='+value);
                    break;
                case 'RepetitionField':
                    var dom_node = $(this);
                    if (value == null) {
                        dom_node.find('.repetition_interval').val(1);
                        dom_node.find('.repetition_type').val('').trigger('change');
                        break;
                    }
                    dom_node.find('.repetition_interval').val(value.interval);
                    dom_node.find('.repetition_type').val(value.type).trigger('change');
                    if (value.metadata.weekdays) checkWithId(dom_node.find('.weekdays'), value.metadata.weekdays);
                    if (value.metadata.months) checkWithId(dom_node.find('.months'), value.metadata.months);
                    if (value.metadata.monthday) dom_node.find('.monthday').val(value.metadata.monthday);
                    if (value.metadata.occurrence) dom_node.find('.occurrence').val(value.metadata.occurrence);
                    if (value.metadata.weekday) dom_node.find('.weekday').val(value.metadata.weekday);
                    if (value.metadata.day) dom_node.find('.day').val(value.metadata.day);
                    if (value.metadata.month) dom_node.find('.month').val(value.metadata.month);
                    break;
                default:
                    $(this).val(value);
                    break;
            }
        })
        
        function checkWithId(dom_node, ids) {
            dom_node.find('input[type="checkbox"]').prop('checked', false);
            $.each(ids, function(key, value) {
                dom_node.find('input[value="'+value+'"]').prop('checked', true);
            });
        }
    },
    
    /**
     * Validates a field
     * @param {jQuery} field_selector
     * @param {bool} show_error
     * @returns {bool}
     */
    validate: function(field_selector, show_error) {
        
    }
    
}