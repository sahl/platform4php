$(function() {
    $('body').append('<div id="platform_allpurpose_dialog"><div id="platform_allpurpose_text"></div><div id="platform_allpurpose_form"></div></div>');
    $('#platform_allpurpose_dialog').platformDialog([]);
});

$.fn.platformDialog = function(buttons, opts) {
    var dialog_buttons = [];
    for (var txt in buttons) {
        if (typeof buttons[txt] == 'function')
            dialog_buttons.push(
                { 
                    text: txt,
                    id: 'dialog_option'+dialog_buttons.length+'_button',
                    click: buttons[txt]
                }
            );
        else
            dialog_buttons.push(buttons[txt]);
    }

    this.each(function() {
        var dia = $(this);
        var standard_options = {
            autoOpen: false,
            modal: true,
            zIndex: 100,
            width: 650,
            buttons: dialog_buttons,
            open: function() {
                $(this).dialog("moveToTop");
                // Fix auto completers append
                $(this).find('.platform_combobox').autocomplete('option', 'appendTo', '.ui-focused');
                // If form elements are present in the form, then focus them
                $('.platform_form input[type!="hidden"]', this).first().focus();
                // Focus on auto-focus field
                $('.platform_autofocus', this).first().focus();
            },
        };
        var opts2 = $.extend(standard_options, opts);
        dia.dialog(opts2);
        dia.on('close', function() {
            $(this).dialog('close');
        })
    })
    return this;
}


function warningDialog(title, text, callback) {
    $('#platform_allpurpose_text').html(text);
    $('#platform_allpurpose_form').children().hide();
    $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
        {
            text: 'OK',
            click: function() {
                $(this).dialog('close');
                if (typeof(callback) == 'function') callback();
            }
        }
    ]).dialog('open');
}

function confirmDialog(title, text, callback_ok, callback_cancel) {
    $('#platform_allpurpose_text').html(text);
    $('#platform_allpurpose_form').children().hide();
    $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
        {
            text: 'OK',
            click: function() {
                $(this).dialog('close');
                if (typeof(callback_ok) == 'function') callback_ok();
            }
        },
        {
            text: 'Cancel',
            click:  function() {
                $(this).dialog('close');
                if (typeof(callback_cancel) == 'function') callback_cancel();
            }
        }        
    ]).dialog('open');
}

function formDialog(title, text, form_id, ok_text, callback_ok, callback_cancel) {
    $('#platform_allpurpose_text').html(text);
    $('#platform_allpurpose_form').children().hide();
    
    // Ensure that the form is moved into place and shown
    $(form_id).appendTo('#platform_allpurpose_form').show();
    
    // (Re)bind submitter
    $(form_id).off('submit.allpurpose_dialog');
    $(form_id).on('submit.allpurpose_dialog', function(data) {
        $('#platform_allpurpose_dialog').dialog('close');
        if (typeof(callback_ok) == 'function') {
            var return_values = {};
            $.each($(form_id).serializeArray(), function(key, value) {
                return_values[value.name] = value.value;
            })
            callback_ok(return_values);
        }
        return false;
    })
    
    if (ok_text == null) ok_text = 'Save';
    
    $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
        {
            text: ok_text,
            click: function() {
                $(form_id).submit();
            }
        },
        {
            text: 'Cancel',
            click:  function() {
                $(this).dialog('close');
                if (typeof(callback_cancel) == 'function') callback_cancel();
            }
        }        
    ]).dialog('open');
    
}


addCustomPlatformFunctionLast(function(item) {
     $('.platform_component_dialog',item).each(function(e) {
        var buttons = [];
        var dialog = $(this);
        $.each($(this).data('buttons'), function(event, title) {
            buttons.push({
                text: title,
                click: function() {dialog.trigger(event);}
            });
        });
        var options = [];
        if (item.find('.dialog_configuration').html()) {
            $.each(JSON.parse(item.find('.dialog_configuration').html()), function(key, element) {
                options[key] = element;
            })
        }
         $(this).platformDialog(buttons, options);
     })
 });


