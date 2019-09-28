$(function() {
     $('.platform_form').submit(function(e) {
         var allowsubmit = true;
         
         // Check required fields
         $('.form_required_field', $(this)).each(function() {
             if ($(this).val().length == 0) {
                 $(this).setError('This is a required field');
                 allowsubmit = false;
             }
             return true;
         });
         
         // Gather hidden fields
         var hiddenfields = [];
         
         $('.form_field:hidden', $(this)).each(function() {
             hiddenfields.push($(this).prop('name'));
         });
         if (hiddenfields.length) $(this).find('[name="form_hiddenfields"]').val(hiddenfields.join(' '));
         
         if (! allowsubmit) {
             e.stopImmediatePropagation();
         }
         
         return allowsubmit;
     });
     
     $('.form_required_field').change(function() {
         $(this).clearError();
     })
     
     $('.platform-password').change(function() {
         $(this).closest('.formfield_container').find('input[type="hidden"]').val(1);
         return true;
     });
})


$.fn.setError = function(text) {
    this.addClass('formfield_error').closest('.formfield_container').find('.formfield_error_container').html(text).slideDown();
}

$.fn.clearError = function() {
    this.filter('.formfield_error').removeClass('formfield_error').closest('.formfield_container').find('.formfield_error_container').slideUp();
}

$.fn.clearForm = function() {
    this.find('input[type!="hidden"][type!="checkbox"],input[type="hidden"][name!="form_action"][name!="form_name"]').val('');
    this.find('[type="checkbox"]').prop('checked', false);
    this.find('select option:first-child').prop('selected', true);
    this.find('.formfield_error').clearError();
    this.find('iframe').each(function() {
        $(this).prop('src', $(this).prop('src'));
    })
}

$.fn.loadValues = function(script, parameters = {}, onload = null) {
    var element = this;
    $.post(script, parameters, function(data) {
        if (data.status == 0) {
            warningDialog('Error loading data', 'Error loading data from '+script+'.<br>'+data.errormessage);
        } else {
            $.each(data.data, function(key, value) {
                var el = element.find('[name="'+key+'"]');
                if (el.length) {
                    if (el.is('input')) el.val(value);
                    else if (el.is('select')) {
                        if (value) el.val(value);
                        else el.find('option:first-child').prop('selected', true);
                    }
                } else {
                    // Try for multicheckbox
                    var el = element.find('#'+element.prop('id')+'_'+key+'.multicheckboxcontainer');
                    if (el.length) {
                        $.each(value, function(key, val) {
                            el.find('input[value="'+val+'"]').prop('checked', true);
                        });
                    } else {
                        // Try for file field
                        var el = element.find('#'+element.prop('id')+'_'+key+'.fileselectorframe');
                        if (el.length) {
                            // Recode url
                            el.prop('src', '/Platform/Field/php/file.php?formname='+el.closest('form').prop('id')+'&fieldname='+key+'&currentfileid='+value);
                        }
                    }
                }
            })
            if (typeof onload == 'function') {
                onload();
            }
        }
    }, 'json');
}