Platform.Dialog = class extends Platform.Component {
    dialog_options = {}
    
    constructor(dom_node, buttons, options) {
        super(dom_node);
        var component = this;
        
        var dialog_buttons = [];
        if (dom_node.data('buttons')) {
            $.each(dom_node.data('buttons'), function(key, value) {
                dialog_buttons.push({text: value, click: function() { dom_node.trigger(key);}});
            })
        }
        
        for (var i in buttons) {
            if (typeof buttons[i] == 'function')
                dialog_buttons.push({text: i, id: 'dialog_option'+dialog_buttons.length+'_button', click: button[i]});
            else
                dialog_buttons.push(buttons[i]);
        }
        
        var standard_options = {
            autoOpen: false,
            modal: true,
            zIndex: 100,
            width: 650,
            buttons: dialog_buttons,
            open: function() {
                $(this).dialog("moveToTop");
                // Fix auto completers append
                $(this).find('.platform_component_form_comboboxfield input[type="text"]').autocomplete('option', 'appendTo', '.ui-focused');
                // If form elements are present in the form, then focus them
                $('.platform_form input[type!="hidden"]', this).first().focus();
                // Focus on auto-focus field
                $('.platform_autofocus', this).first().focus();
            }
        }
        
        if (dom_node.find('.dialog_configuration').html()) {
            if (! options) options = [];
            $.each(JSON.parse(dom_node.find('.dialog_configuration').html()), function(key, element) {
                options[key] = element;
            })
        }
        
        dom_node.on('close', function() {
            component.close();
        })
        
    
        this.dialog_options = $.extend(standard_options, options);
    }
    
    initializeLast() {
        var component = this;
        this.dom_node.dialog(this.dialog_options);
        this.dom_node.on('dialog_close', function() {
            component.close();
        })
    }
    
    static warningDialog(title, text, callback) {
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

    static confirmDialog(title, text, callback_ok, callback_cancel) {
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

    /**
     * Open a dialog displaying a form, the form DOM element will be moved to the dialog
     * @param html title
     * @param html text Text to display above the form
     * @param string form_id ID of the form, must begin with '#'
     * @param html ok_text Text to display in the "OK" button
     * @param function callback_ok Will be called as callback_ok(values, close_function) 
     * @param function callback_open Will be called to determine if the dialog should be opened, should return true/false
     * @param function callback_cancel Will be called if the user clicks on "Cancel"
     */
    static formDialog(title, text, form_id, ok_text, callback_ok, callback_open, callback_cancel) {
        $('#platform_allpurpose_text').html(text);
        
        // We want the form component
        form_id = form_id + '_component';
        var form = $(form_id).platformComponent();

        // Ensure that the form is moved into place and shown
        var form_original_parent = $(form_id).parent();

        $(form_id).appendTo('#platform_allpurpose_form').show();

        // (Re)bind submitter
        $(form_id).off('submit.allpurpose_dialog');
        $(form_id).on('submit.allpurpose_dialog', function(data) {
            // validate the form, this will also fill in the 'form_hiddenfields' element
            if (!form.validate())
                return;

            if (typeof(callback_ok) == 'function') {
                var return_values = $(form_id).platformComponent().getValues();
                
                callback_ok(return_values, function() {
                    $('#platform_allpurpose_dialog').dialog('close');
                    // Move form back in place
                    $(form_id).appendTo(form_original_parent);
                    // Be sure to clean form area
                    $('#platform_allpurpose_form').html('');
                });
            } else {
                $('#platform_allpurpose_dialog').dialog('close');
                // Move form back in place
                $(form_id).appendTo(form_original_parent);
                // Be sure to clean form area
                $('#platform_allpurpose_form').html('');
            }
            return false;
        })

        if (ok_text == null) ok_text = 'Save';

        var open_dialog_form = true;
        if (callback_open) {
            if (! callback_open()) open_dialog_form = false;
        }

        if (open_dialog_form) {
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
                    }
                }        
            ]).dialog('option', 'close', function() {  
                if (typeof(callback_cancel) == 'function') callback_cancel();
                // Move form back in place
                $(form_id).appendTo(form_original_parent);
            }).dialog('open');
        }
    }
    
    static componentDialog(title, text, component_id, ok_text, callback_ok, callback_open, callback_cancel) {
        $('#platform_allpurpose_text').html(text);
        
        // Ensure that the component is moved into place and shown
        var component_original_parent = $(component_id).parent();
        
        $(component_id).appendTo('#platform_allpurpose_form').show();
        
        if (ok_text == null) ok_text = 'Save';

        var open_dialog = true;
        if (callback_open) {
            if (! callback_open()) open_dialog = false;
        }

        if (open_dialog) {
            $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
                {
                    text: ok_text,
                    click: function() {
                        callback_ok();
                    }
                },
                {
                    text: 'Cancel',
                    click:  function() {
                        $(this).dialog('close');
                    }
                }        
            ]).dialog('option', 'close', function() {  
                if (typeof(callback_cancel) == 'function') callback_cancel();
                // Move component back in place
                $(component_id).appendTo(component_original_parent);
            }).dialog('open');
        }
    }
    
    
    open() {
        this.dom_node.dialog('open');
    }
    
    close() {
        this.dom_node.dialog('close');
    }
    
    static closeGeneral() {
        $('#platform_allpurpose_dialog').dialog('close');
    }
}

Platform.Component.bindClass('platform_dialog', Platform.Dialog);

// Write the standard dialog container to the document.
$(function() {
    $('body').append('<div id="platform_allpurpose_dialog"><div id="platform_allpurpose_text"></div><div id="platform_allpurpose_form"></div></div>');
    var dialog = new Platform.Dialog($('#platform_allpurpose_dialog'));
    dialog.initialize();
    dialog.initializeLast();
});

