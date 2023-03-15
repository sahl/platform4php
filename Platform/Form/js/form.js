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
    var attach_element = this;
    var form = attach_element.closest('form');

    $.each(values, function(key, data) {
        var fieldtype = data.fieldtype;
        var value = data.value;
        var escaped_key = key.replace(/\[/g,"\\[").replace(/\]/g,"\\]");
        
        console.log('Target '+key);
        console.log(value);
        
        // First we try to see if there is a special component for this field
        if (attach_element.find('.platform_field_component_'+key).length) {
            attach_element.find('.platform_field_component_'+key).trigger('setvalue', value);
        } else {
            switch (fieldtype) {
                case 'CheckboxField':
                    var dom_node = attach_element.find('[name="'+key+'"]');
                    dom_node.prop('checked', value == 1);
                    break;
                case 'TexteditorField':
                    var dom_node = attach_element.find('[name="'+key+'"]');
                    dom_node.summernote('reset');
                    dom_node.summernote('code', value);
                    break;
                case 'CurrencyField':
                    attach_element.find('[name="'+key+'[localvalue]"]').val(value.localvalue);
                    attach_element.find('[name="'+key+'[currency]"]').val(value.currency);
                    attach_element.find('[name="'+key+'[foreignvalue]"]').val(value.foreignvalue);
                    break;
                case 'SelectField':
                    var dom_node = attach_element.find('[name="'+key+'"]');
                    if (value !== null) dom_node.val(value);
                    else dom_node.find('option:first-child').prop('selected', true);
                    break;
                case 'DatarecordcomboboxField':
                case 'IndexedComboboxField':
                case 'ComboboxField':
                    var dom_node = attach_element.find('[name="'+key+'[visual]"]');
                    dom_node.val(value.visual).data('validated_value', value.visual);
                    dom_node.prev().val(value.id);
                    break;
                case 'MulticheckboxField':
                    var dom_node = attach_element.find('#'+form.attr('id')+'_'+key+'.multi_checkbox_container');
                    checkWithId(dom_node, value);
                    break;
                case 'MultidatarecordcomboboxField':
                    var dom_node = attach_element.find('#'+form.attr('id')+'_'+escaped_key+'_container .platform_form_multiplier');
                    $.each(value, function(key, val) {
                        dom_node.find('input[type="hidden"]:last').val(val.id);
                        dom_node.find('input[type="text"]:last').val(val.visual).data('validated_value', value.visual).trigger('keyup');
                    });
                    break;
                case 'MultiplierSection':
                    var dom_node = attach_element.find('#'+form.attr('id')+'_'+key+'_container .platform_form_multiplier:first');
                    var i = 0;
                    $.each(value, function(dummy, val) {
                        // This is each section
                        var insert_node = dom_node.children('.platform_form_multiplier_element:last');
                        var new_element = {};
                        $.each(val, function(inner_key, inner_val) {
                            inner_key = key+'['+(i)+']['+inner_key+']';
                            new_element[inner_key] = inner_val;
                            
                        });
                        insert_node.attachValues(new_element);
                        insert_node.find('input[type!="checkbox"],textarea').trigger('keyup');
                        i++;
                    });
                    break;
                case 'MultiField':
                    var dom_node = attach_element.find('#'+form.attr('id')+'_'+key+'_container .platform_form_multiplier');
                    $.each(value, function(key, val) {
                        dom_node.find('input[type="text"]:last').val(val).trigger('keyup');
                    });
                    break;
                case 'FileField':
                    var dom_node = attach_element.find('#'+form.attr('id')+'_'+key+'.platform_file_input_frame');
                    dom_node.prop('src', '/Platform/Form/php/file.php?form_name='+dom_node.closest('form').attr('id')+'&field_name='+key+'&file_id='+value);
                    break;
                case 'RepetitionField':
                    var dom_node = attach_element.find('#'+form.attr('id')+'_'+key+'_container.repetition_field');
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
                    var dom_node = attach_element.find('[name="'+key+'"]');
                    dom_node.val(value);
                    break;
            }
        }
    })

    this.trigger('dataloaded');
    
    function checkWithId(dom_node, ids) {
        dom_node.find('input[type="checkbox"]').prop('checked', false);
        $.each(ids, function(key, value) {
            dom_node.find('input[value="'+value+'"]').prop('checked', true);
        });
    }
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