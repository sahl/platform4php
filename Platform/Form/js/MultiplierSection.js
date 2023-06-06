Platform.Form.MultiplierSection = class extends Platform.Form.Field {
    
    initialize() {
        var component = this;
        // Add handlers to find if something has changed
        console.log('Apply Multiplier to '+component.dom_node.prop('id'));
        this.dom_node.find('.platform_form_multiplier_element').each(function() {
            component.primeRow($(this));
        })
    }
    
    primeRow(row) {
        // Check if we entered another multiplier
        if (! row.closest('.platform_form_multiplier').is(this.dom_node)) {
            console.log('We went to deep. Leave this for another.');
            return;
        }
        var component = this;
        row.find('input[type!="checkbox"],textarea').keyup(function() {
            component.checkForChanges($(this).closest('.platform_form_multiplier_element'));
        });
        row.find('input[type="checkbox"]').click(function() {
            component.checkForChanges($(this).closest('.platform_form_multiplier_element'));
        });
        row.find('select').change(function() {
            component.checkForChanges($(this).closest('.platform_form_multiplier_element'));
        });
    }
    
    checkForChanges(row) {
        console.log('Change check in '+this.dom_node.prop('id'));
        // Check if we entered another multiplier
        if (! row.closest('.platform_form_multiplier').is(this.dom_node)) {
            console.log('We went to deep. Leave this for another.');
            return;
        }
        // Check if we need to expand
        if ((row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)')) && this.gotValues(row)) {
            // We need to expand.
            var new_row = row.clone(true); // We need to set true to bring data values along
            new_row.off().find('*').off(); // Destroy all event listeners
            new_row.find('*').data('platform_component', null); // Destroy platform component object
            new_row.insertAfter(row);
            this.adjustNames();
            Platform.apply(new_row); // Reapply platform
            new_row.find('.platform_form_field').each(function() {
                var field = $(this).platformComponent();
                field.clear();
            });
            this.primeRow(new_row);
            this.dom_node.trigger('row_added');
        } else {
            // Check if we need to contract
            if (! this.gotValues(row) && ! (row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)'))) {
                var container = $(this).closest('.platform_form_multiplier_element');
                if (row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)'))
                    row.prev().find('input[type!="hidden"],textarea').first().focus();
                else
                    row.next().find('input[type!="hidden"],textarea').first().focus();
                row.remove();
                this.adjustNames();
                container.trigger('row_deleted');
            }
        }
        return true;
    }
    
    adjustNames() {
        var component = this.dom_node;
        // Determine number of array markers in base name
        console.log('I am examining '+component.prop('id'));
        var base_name = component.prop('id').substring(component.closest('form').prop('id').length+1);
        base_name = base_name.substring(0,base_name.length-10);
        console.log('Extract base name: '+base_name);
        // Catch all before relevant counter
        var regexp_string = '/('+base_name.replace(/(\[|\])/gi, '\\$1')+')';
        // Skip counter and get all following
        regexp_string += '\\[\\d*\\](\\.*)/i';
        console.log(regexp_string);
        var regexp = eval(regexp_string);
        var i = 0;
        component.find('.platform_form_multiplier_element').each(function() {
            if (! $(this).closest('.platform_form_multiplier').is(component)) {
                // We went to deep
                return true;
            }
            $(this).find('*').each(function() {
                var name = $(this).prop('name');
                var id = $(this).prop('id');
                var label_for = $(this).prop('for');
                if (name) {
                    var new_name = name.replace(regexp, '$1['+i+']$2');
                    $(this).prop('name', new_name);
                }
                if (id) {
                    var new_id = id.replace(regexp, '$1['+i+']$2');
                    $(this).prop('id', new_id);
                }
                if (label_for) {
                    var new_label_for = label_for.replace(regexp, '$1['+i+']$2');
                    $(this).prop('for', label_for);
                }
            })
            /*
            $(this).find('iframe.file_select_frame').each(function() {
                var name = $(this).data('name');
                var new_name = name.replace(regexp, '$1['+i+']$2');
                var id = $(this).attr('id');
                var new_id = id.replace(regexp, '$1['+i+']$2');
                $(this).prop('id', new_id);
                // Recode url if source changed
                var src = '/Platform/Field/php/file.php?form_name='+$(this).closest('form').attr('id')+'&field_name='+new_name;
                if (name != new_name) $(this).prop('src', src);
            })
            */
            i++;
        })
    }
    
    gotValues(row) {
        var result = false;
        row.find('.platform_form_field').each(function() {
            var field_component = $(this).platformComponent();
            if (! field_component.isEmpty()) {
                result = true;
                return false;
            }
            return true;
        })
        return result;
    }
    
    setValue(value) {
        var i = 0;
        var element = this.dom_node;
        var base_id = element.prop('id');
        $.each(value, function(index, val) {
            // This is each section
            var insert_node = element.children('.platform_form_multiplier_element:last');
            $.each(val, function(inner_key, inner_val) {
                var inner_id = base_id+'['+(i)+']['+inner_key+']';
                inner_id = inner_id.replace(/\[/g,"\\[").replace(/\]/g,"\\]");
                //Platform.Form.setValue($('#'+inner_id), inner_val);
            });
            // Can't we do better than keyup?
            insert_node.find('input[type!="checkbox"],textarea').trigger('keyup');
            i++;
        });
    }
    
}

Platform.Component.bindClass('platform_component_form_multiplier_section', Platform.Form.MultiplierSection);

/*
Platform.addCustomFunction(function(item) {
    platform_add_multiplier_functionality($('.platform_form_multiplier_element', item));
    
    $('.platform_sortable', item).sortable({
        stop: function() {
            platform_multiplier_fixnames($(this));
        },
        cancel: "input,textarea,button,select,option,.note-editor",
        items: "div.platform_form_multiplier_element:not(:last-child)"
    }).find('.platform_form_multiplier_element').css('cursor', 'move');
});

*/


function xxplatform_add_multiplier_functionality(element) {
    $(element).find('input[type!="checkbox"],textarea').keyup(platform_handle_multiplier_change);
    $(element).find('input[type="checkbox"]').click(platform_handle_multiplier_change);
    $(element).find('select').change(platform_handle_multiplier_change);
}

function xxplatform_detect_values(element) {
    var result = false;
    $('input[type!="hidden"],select,textarea', element).each(function() {
        if ($(this).is('[type="checkbox"]')) {
            if ($(this).is(':checked')) {
                result = true;
                return false;
            }
        } else if ($(this).val()) {
            result = true;
            return false;
        }
    });
    return result;
}

function xxplatform_handle_multiplier_change() {
    var row = $(this).closest('.platform_form_multiplier_element');
    // Check if we need to expand
    if ((row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)')) && $(this).val() != '') {
        // We need to expand.
        var new_row = row.clone(true); // We need to set true to bring data values along
        new_row.off().find('*').off(); // Destroy all event listeners
        new_row.insertAfter(row);
        new_row.find('textarea,input[type!="checkbox"]').val('');
        new_row.find('input[type="checkbox"]').attr('checked', false);
        new_row.find('.formfield_error').removeClass('formfield_error');
        new_row.find('.platform_form_multiplier_element:not(:first-child)').remove();
        new_row.find('.formfield_error_container').hide();
        platform_multiplier_fixnames($(this).closest('.platform_form_multiplier'));
        new_row.applyPlatformFunctions();
        platform_add_multiplier_functionality(new_row);
        row.closest('.platform_form_multiplier').trigger('row_added');
    } else {
        // We need to remove this row if it's empty, except if it is the last row or the last row of it's kind
        if (($(this).val() == '' || $(this).is('[type="checkbox"]:not(:checked)')) && ! platform_detect_values(row) && ! (row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)'))) {
            var container = $(this).closest('.platform_form_multiplier');
            if (row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)'))
                row.prev().find('input[type!="hidden"],textarea').first().focus();
            else
                row.next().find('input[type!="hidden"],textarea').first().focus();
            row.remove();
            platform_multiplier_fixnames(container);
            container.trigger('row_deleted');
        }
    }
    return true;
}

function xxplatform_multiplier_fixnames(element) {
    // Determine number of array markers in base name
    var base_name = element.prop('id').substring(element.closest('form').prop('id').length+1);
    // Catch all before relevant counter
    var regexp_string = '/('+base_name.replace(/(\[|\])/gi, '\\$1')+')';
    // Skip counter and get all following
    regexp_string += '\\[\\d*\\](\\.*)/i';
    console.log(regexp_string);
    var regexp = eval(regexp_string);
    var i = 0;
    element.children('.platform_form_multiplier_element').each(function() {
        $(this).find('input,select,textarea').each(function() {
            var name = $(this).prop('name');
            var new_name = name.replace(regexp, '$1['+i+']$2');
            var realname = $(this).data('realname');
            if (realname) {
                var new_realname = realname.replace(regexp, '$1['+i+']$2');
                if (new_realname) $(this).data('realname', new_realname);
            }
            var id = $(this).prop('id');
            var new_id = id.replace(regexp, '$1['+i+']$2');
            $(this).prop('name', new_name).prop('id', new_id);
            $(this).closest('.platform_form_field_container').find('label').prop('for', new_name);
            $(this).closest('.platform_form_field_container').prop('id', new_id+'_container');
        })
        $(this).find('.platform_form_multiplier').each(function() {
            var id = $(this).prop('id');
            var new_id = id.replace(regexp, '$1['+i+']$2');
            $(this).prop('id', new_id);
            $(this).closest('.platform_form_field_container').prop('id', new_id+'_container');
        })
        $(this).find('.platform_formfield_container').each(function() {
            var id = $(this).prop('id');
            var new_id = id.replace(regexp, '$1['+i+']$2');
            $(this).prop('id', new_id);
            // TODO: We cannot find the main field for the label
        })
        $(this).find('iframe.file_select_frame').each(function() {
            var name = $(this).data('name');
            var new_name = name.replace(regexp, '$1['+i+']$2');
            var id = $(this).attr('id');
            var new_id = id.replace(regexp, '$1['+i+']$2');
            $(this).prop('id', new_id);
            // Recode url if source changed
            var src = '/Platform/Field/php/file.php?form_name='+$(this).closest('form').attr('id')+'&field_name='+new_name;
            if (name != new_name) $(this).prop('src', src);
        })
        i++;
    })
}