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
        $('.form_required_field', $(this)).each(function() {
            if ($(this).val().length == 0 && $(this).is(':visible')) {
                $(this).setError('This is a required field');
                allowsubmit = false;
            }
            return true;
        });
        
        // Validate component fields
        $('.platform_component_fieldcomponent').each(function() {
            $(this).trigger('validate');
            if ($(this).hasClass('platform_form_field_error')) allowsubmit = false;
        })

        // Gather hidden fields
        var hiddenfields = [];

        $('.platform_form_field:hidden,.platform_form_field:disabled', $(this)).each(function() {
            // We accept hidden texteditors
            if ($(this).is('.texteditor')) return true;
            var name = $(this).prop('name');
            if ($(this).data('realname')) name = $(this).data('realname');
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
    this.find('input[type!="hidden"][type!="checkbox"],input[type="hidden"][name!="form_event"][name!="form_name"]').not('.platform_dont_clear').val('');
    this.find('textarea').not('.platform_dont_clear').val('');
    this.find('textarea.texteditor').summernote('reset');
    this.find('[type="checkbox"]').not('.platform_dont_clear').prop('checked', false);
    this.find('select').not('.platform_dont_clear').find('option:first-child').prop('selected', true);
    this.find('.platform_form_multiplier').each(function() {
        $(this).find('.platform_form_multiplier_element:not(:first)').remove();
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
    var element = this;
    $.each(values, function(key, value) {
        // Try for component
        if (element.find('.platform_field_component_'+key).length) {
            element.find('.platform_field_component_'+key).trigger('setvalue', value);
        } else {
            var el = element.find('[name="'+key+'"]');
            if (el.length) {
                if (el.is('input,textarea')) {
                    if (el.is('[type=checkbox]')) {
                        el.prop('checked', value == 1);
                    } else {
                        // Extract IDs from complex fields
                        if (typeof value === 'object' && value !== null && value.id) value = value.id;
                        el.val(value);
                        if (el.is('.texteditor')) {
                            el.summernote('reset');
                            el.summernote('code', value);
                        }
                    }
                }
                else if (el.is('select')) {
                    if (value !== null) el.val(value);
                    else el.find('option:first-child').prop('selected', true);
                }
            } else {
                // Try for currency
                if (value.localvalue) {
                    console.log('Local '+value.localvalue+' for '+key);
                    element.find('[name="'+key+'[localvalue]"]').val(value.localvalue);
                    element.find('[name="'+key+'[currency]"]').val(value.currency);
                    element.find('[name="'+key+'[foreignvalue]"]').val(value.foreignvalue);
                    return true; // Continue
                }
                // Try for combobox
                var el = element.find('[name="'+key+'[visual]"]');
                if (el.length) {
                    el.val(value.visual);
                    el.prev().val(value.id);
                } else {
                    // Try for multicheckbox
                    var el = element.find('#'+element.attr('id')+'_'+key+'.multi_checkbox_container');
                    if (el.length) {
                        $.each(value, function(key, val) {
                            el.find('input[value="'+val+'"]').prop('checked', true);
                        });
                    } else {
                        // Try for multiplier
                        var el = element.find('#'+element.attr('id')+'_'+key+'_container .platform_form_multiplier');
                        if (el.length) {
                            $.each(value, function(key, val) {
                                el.find('input[type="hidden"]:last').val(val.id);
                                el.find('input[type="text"]:last').val(val.visual).trigger('keyup');
                            });
                        } else {
                            // Try for file field
                            var el = element.find('#'+element.attr('id')+'_'+key+'.platform_file_input_frame');
                            if (el.length) {
                                // Recode url
                                el.prop('src', '/Platform/Form/php/file.php?form_name='+el.closest('form').attr('id')+'&field_name='+key+'&file_id='+value);
                            }
                        }
                    }
                }
            }
        }
    })
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