Platform.Dialog = class extends Platform.Component {
    dialog_options = {}
    
    /**
     * Constructs a dialog
     * @param {jquery} dom_node DOM node containing the dialog component
     * @param {object} buttons Buttons
     * @param {object} options Jquery UI Dialog options
     */
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
    
    /**
     * Displays a warning dialog
     * @param {string} title Title of dialog
     * @param {html} text Text of dialog
     * @param {function} callback Function to call when the user clicks OK
     */
    static warningDialog(title, text, callback) {
        $('#platform_allpurpose_text').html(text);
        $('#platform_allpurpose_container').children().hide();
        $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
            {
                text: 'OK',
                click: function() {
                    Platform.Dialog.closeGeneral();
                    if (typeof(callback) == 'function') callback();
                }
            }
        ]).dialog('open');
    }

    /**
     * Display a confirm dialog
     * @param {string} title Title of dialog
     * @param {html} text Text of dialog
     * @param {function} callback_ok Function to call when the user clicks OK
     * @param {function} callback_cancel Function to call if the user cancels
     */
    static confirmDialog(title, text, callback_ok, callback_cancel) {
        $('#platform_allpurpose_text').html(text);
        $('#platform_allpurpose_container').children().hide();
        $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
            {
                text: 'OK',
                click: function() {
                    Platform.Dialog.closeGeneral();
                    if (typeof(callback_ok) == 'function') callback_ok();
                }
            },
            {
                text: 'Cancel',
                click:  function() {
                    Platform.Dialog.closeGeneral();
                    if (typeof(callback_cancel) == 'function') callback_cancel();
                }
            }        
        ]).dialog('open');
    }
    
    static component_in_dialog_dom = null;
    
    static component_original_parent_dom = null;

    /**
     * Move a given DOM node into the general purpose dialog and remember where
     * we took it from
     * @param {jquery} dom_node DOM node to move
     */
    static #moveToGeneralDialog(dom_node) {
        Platform.Dialog.component_original_parent_dom = dom_node.parent();
        Platform.Dialog.component_in_dialog_dom = $(dom_node);
        $(dom_node).appendTo('#platform_allpurpose_container').show();
    }
    
    /**
     * Open a dialog displaying a form, the form DOM element will be moved to the dialog.
     * Pressing OK will submit the form, but won't close the dialog. Use Platform.Dialog.closeGeneral()
     * @param {string} title
     * @param {html} text Text to display above the form
     * @param {string} form_id ID of the form, must begin with '#'
     * @param {html} ok_text Text to display in the "OK" button
     * @param {function} callback_ok Will be called when the form is submitted (and if it validates)
     * @param {function} callback_open Will be called to determine if the dialog should be opened, should return true/false
     * @param {function} callback_cancel Will be called if the user clicks on "Cancel"
     */
    static formDialog(title, text, form_id, ok_text, callback_ok, callback_open, callback_cancel) {
        $('#platform_allpurpose_text').html(text);
        
        // We want the form component
        form_id = $(form_id);
        var form_component_id = form_id.prop('id') + '_component';
        var form = form_id.platformComponent();

        // (Re)bind submitter
        $(form_id).off('submit.allpurpose_dialog');
        $(form_id).on('submit.allpurpose_dialog', function(data) {
            if (typeof(callback_ok) === 'function') {
                var return_values = form.getValues();
                
                callback_ok(return_values);
            }
            return true;
        })

        if (ok_text === null) ok_text = 'Save';

        var open_dialog_form = true;
        if (callback_open) {
            if (! callback_open()) open_dialog_form = false;
        }

        if (open_dialog_form) {
            // Ensure that the form is moved into place and shown
            Platform.Dialog.#moveToGeneralDialog(form.dom_node);
        
            $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
                {
                    text: ok_text,
                    click: function() {
                        $(form_id).submit();
                    }
                },
                {
                    text: 'Cancel',
                    click:  Platform.Dialog.closeGeneral
                }        
            ]).dialog('open');
        }
    }
    
    /**
     * Open a dialog displaying a component, which is moved inside the dialog
     * @param {string} title
     * @param {html} text Text to display above the form
     * @param {string} component_id ID of the component, must begin with '#'
     * @param {html} ok_text Text to display in the "OK" button
     * @param {function} callback_ok Will be called when the user click the OK-button
     * @param {function} callback_open Will be called to determine if the dialog should be opened, should return true/false
     * @param {function} callback_cancel Will be called if the user clicks on "Cancel"
     */
    static componentDialog(title, text, component_id, ok_text, callback_ok, callback_open, callback_cancel) {
        $('#platform_allpurpose_text').html(text);
        
        if (ok_text == null) ok_text = 'Save';

        var open_dialog = true;
        if (callback_open) {
            if (! callback_open()) open_dialog = false;
        }

        if (open_dialog) {
            // Ensure that the component is moved into place and shown
            component_id = $(component_id);
            Platform.Dialog.#moveToGeneralDialog(component_id);
            
            $('#platform_allpurpose_dialog').dialog('option', 'title', title).dialog('option', 'buttons', [
                {
                    text: ok_text,
                    click: function() {
                        callback_ok();
                    }
                },
                {
                    text: 'Cancel',
                    click: Platform.Dialog.closeGeneral
                }        
            ]).dialog('open');
        }
    }
    
    /**
     * Open this dialog
     */
    open() {
        this.dom_node.dialog('open');
    }
    
    /**
     * Close this dialog
     */
    close() {
        this.dom_node.dialog('close');
    }
    
    /**
     * Close the general dialog
     */
    static closeGeneral() {
        $('#platform_allpurpose_dialog').dialog('close');
    }
}

Platform.Component.bindClass('platform_dialog', Platform.Dialog);

// Write the standard dialog container to the document.
$(function() {
    $('body').append('<div id="platform_allpurpose_dialog"><div id="platform_allpurpose_text"></div><div id="platform_allpurpose_container"></div></div>');
    var dialog = new Platform.Dialog($('#platform_allpurpose_dialog'), [], {
        close: function() {
            console.log('Calling close');
            // If a component is contained, then move it back to its original position
            if (Platform.Dialog.component_in_dialog_dom !== null) {
                console.log('Moving back');
                Platform.Dialog.component_in_dialog_dom.appendTo(Platform.Dialog.component_original_parent_dom);
                Platform.Dialog.component_in_dialog_dom = null;
                //$('#platform_allpurpose_container').html('');
            }
        }
    });
    dialog.initialize();
    dialog.initializeLast();
});

