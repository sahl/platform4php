addCustomPlatformFunction(function(item) {
     $('.platform_form',item).submit(function(e) {
        var allowsubmit = true;

        // Hide last item of multipliers as these should always be empty and not submitted or validated.
        $('.platform_form_multiplier_element', $(this)).each(function() {
            if ($(this).next().is(':last-child')) $(this).hide();
        });

        // Check required fields
        $('.form_required_field', $(this)).each(function() {
            if ($(this).val().length == 0 && $(this).is(':visible')) {
                $(this).setError('This is a required field');
                allowsubmit = false;
            }
            return true;
        });

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
        }

         // Show multipliers again
        $('.platform_form_multiplier_element', $(this)).show();
         
        return allowsubmit;
     });

    // Clear errors when changing fields
     $('.platform_form_field',item).change(function() {
         $(this).clearError();
     });
          
     // Indicate on password-field when it is updated.
     $('.platform-password',item).change(function() {
         $(this).closest('.platform_formfield_container').find('input[type="hidden"]').val(1);
         return true;
     });
     
     // Focus on auto-focus field
     $('.platform_autofocus:first').focus();
     
     // Autosize required textareas
     autosize($('textarea.autosize', item));
});


$.fn.setError = function(text) {
    this.addClass('formfield_error').closest('.platform_formfield_container').find('.formfield_error_container').html(text).slideDown();
    return this;
}

$.fn.clearError = function() {
    this.filter('.formfield_error').removeClass('formfield_error').closest('.platform_formfield_container').find('.formfield_error_container').slideUp();
    return this;
}

$.fn.clearForm = function() {
    this.find('input[type!="hidden"][type!="checkbox"],input[type="hidden"][name!="form_event"][name!="form_name"]').not('.platform_dont_clear').val('');
    this.find('[type="checkbox"]').not('.platform_dont_clear').prop('checked', false);
    this.find('select').not('.platform_dont_clear').find('option:first-child').prop('selected', true);
    this.find('.platform_form_multiplier').each(function() {
        $(this).find('.platform_form_multiplier_element:not(:first)').remove();
    });
    this.find('.formfield_error').clearError();
    this.find('iframe').each(function() {
        $(this).prop('src', $(this).prop('src'));
    })
    return this;
}

$.fn.loadValues = function(script, parameters = {}, onload = null) {
    var element = this;
    $.post(script, parameters, function(data) {
        if (data.status == 0) {
            warningDialog('Error loading data', data.errormessage);
        } else {
            $.each(data.data, function(key, value) {
                var el = element.find('[name="'+key+'"]');
                console.log('Find field '+key);
                if (el.length) {
                    if (el.is('input,textarea')) {
                        if (el.is('[type=checkbox]')) {
                            el.prop('checked', value == 1);
                        } else {
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
                            var el = element.find('#'+element.attr('id')+'_'+key+'_container.platform_form_multiplier');
                            if (el.length) {
                                $.each(value, function(key, val) {
                                    el.find('input[type="hidden"]:last').val(val.id);
                                    el.find('input[type="text"]:last').val(val.visual).trigger('keyup');
                                });
                            } else {
                                // Try for file field
                                var el = element.find('#'+element.attr('id')+'_'+key+'.file_select_frame');
                                if (el.length) {
                                    // Recode url
                                    el.prop('src', '/Platform/Field/php/file.php?form_name='+el.closest('form').attr('id')+'&field_name='+key+'&file_id='+value);
                                }
                            }
                        }
                    }
                }
            })
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
        console.log('Trying to assign error '+error_message+' to ID: '+form_id);
        console.log('Searching got '+$('#'+form_id).length+' results');
        $('#'+form_id, form).setError(error_message);
    })
}