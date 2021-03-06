$(function() {
    $('body').append('<div id="platform_allpurpose_dialog"><div id="platform_allpurpose_text"></div></div>');
    $('#platform_allpurpose_dialog').platformDialog([]);
});

$.fn.platformDialog = function(buttons, opts) {
    var dialog_buttons = [];
    for (var txt in buttons) {
        if (typeof buttons[txt] == 'function')
            dialog_buttons.push({ text: txt,
                           id: 'dialog_option'+dialog_buttons.length+'_button',
                           click: buttons[txt]
                          });
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
         $(this).platformDialog(buttons);
     })
 });


