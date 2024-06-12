Platform.Form.MultiplierSection = class extends Platform.Form.Field {
    
    initialize() {
        var component = this;
        // Add handlers to find if something has changed
        this.dom_node.find('.platform_form_multiplier_element').each(function() {
            component.primeRow($(this));
        })
    }
    
    primeRow(row) {
        // Check if we entered another multiplier
        if (! row.closest('.platform_form_multiplier').is(this.dom_node)) {
            return;
        }
        var component = this;
        row.find('input[type!="checkbox"],textarea').keyup(function() {
            component.checkForChanges($(this).closest('.platform_form_multiplier_element'));
            return true;
        });
        row.find('input[type="checkbox"]').change(function() {
            // Check if checked
            var is_checked = $(this).is(':checked');
            component.checkForChanges($(this).closest('.platform_form_multiplier_element'));
            // Recheck as something is lost in translation
            $(this).prop('checked', is_checked);
            return true;
        });
        row.find('select').change(function() {
            component.checkForChanges($(this).closest('.platform_form_multiplier_element'));
            return true;
        });
    }
    
    clear() {
        this.dom_node.find('.platform_form_multiplier_element:not(:first-child)').remove();
        $.each(this.getChildren(), function(i, val) {
            val.clear();
        });
    }    
    
    checkForChanges(row) {
        // Check if we entered another multiplier
        if (! row.closest('.platform_form_multiplier').is(this.dom_node)) {
            return;
        }
        // Check if we need to expand
        if ((row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)')) && this.gotValues(row)) {
            // We need to expand.
            // Advice all components that they are about to be cloned.
            row.find('.platform_applied').trigger('platform_multiplier_section_before_clone');
            // Clone
            var new_row = row.clone(true); // We need to set true to bring data values along
            // Advice all components that cloning is done
            row.find('.platform_applied').trigger('platform_multiplier_section_after_clone');
            new_row.off().find('*').off(); // Destroy all event listeners
            new_row.find('*').data('platform_component', null); // Destroy platform component object
            new_row.insertAfter(row);
            this.adjustNames();
            Platform.apply(new_row); // Reapply platform
            new_row.find('.platform_form_field_component').each(function() {
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
        var base_name = component.prop('id').substring(component.closest('form').prop('id').length+1);
        base_name = base_name.substring(0,base_name.length-10);
        // Catch all before relevant counter
        var regexp_string = '/('+base_name.replace(/(\[|\])/gi, '\\$1')+')';
        // Skip counter and get all following
        regexp_string += '\\[\\d*\\](\\.*)/i';
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
        row.find('.platform_form_field_component').each(function() {
            var field_component = $(this).platformComponent();
            if (! field_component.isEmpty() && $(this).data('componentclass') != 'Platform\\Form\\HiddenField') {
                result = true;
                return false;
            }
            return true;
        })
        return result;
    }
    
    getValue() {
        var values = [];
        // Expression for fetching base name field[0][name] => name
        var regexp = /\[([^\]]*)\]$/;
        // Loop all nearest multiplier elements
        this.dom_node.closestChildren('.platform_form_multiplier_element').each(function() {
            var inner_values = {};
            // Loop all nearest contained fields
            $(this).closestChildren('.platform_form_field_component').each(function() {
                var component = $(this).platformComponent();
                var name = component.getName();
                // Replace name
                var result = name.match(regexp);
                if (result.length == 2) name = result[1];
                var value = component.getValue();
                inner_values[name] = value;
            });
            values.push(inner_values);
        });
        return values;
    }
    
    setValue(value) {
        var i = 0;
        var component = this;
        var element = this.dom_node;
        var base_id = new String(element.prop('id'));
        base_id = base_id.substring(0,base_id.length-10);
        $.each(value, function(index, val) {
            // This is each section
            var insert_node = element.children('.platform_form_multiplier_element:last');
            $.each(val, function(inner_key, inner_val) {
                var inner_id = base_id+'['+(i)+']['+inner_key+']';
                inner_id = inner_id.replace(/\[/g,"\\[").replace(/\]/g,"\\]")+'_component';
                var input_component = $('#'+inner_id).platformComponent();
                if (input_component) {
                    input_component.setValue(inner_val);
                    component.checkForChanges(input_component.dom_node.closest('.platform_form_multiplier_element'));
                }
                //Platform.Form.setValue($('#'+inner_id), inner_val);
            });
            // Can't we do better than keyup?
            insert_node.find('input[type!="checkbox"],textarea').trigger('keyup');
            i++;
        });
    }
    
}

Platform.Component.bindClass('platform_component_form_multiplier_section', Platform.Form.MultiplierSection);