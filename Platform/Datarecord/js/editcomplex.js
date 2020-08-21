addPlatformComponentHandlerFunction('datarecordeditcomplex', function(element) {

    // Locate table
    var table = null;
    var name = element.data('name');
    var classname = element.data('class');
    var short_classname = element.data('shortclass');
    var script = element.data('io_datarecord');

    var table_div = element.find('.platform_component_table');
    var menu_div = element.find('#'+short_classname+'_table_menu');

    var form = $('#'+short_classname+'_form');
    var dialog = $('#'+short_classname+'_edit_dialog');
    
    var column_select_dialog = $('#'+table_div.prop('id')+'_component_select_dialog');
    
    
    table_div.on('component_ready', function() {
        table = getTableByID('#'+table_div.prop('id'));
        prepareTable(table);

        prepareMenu(menu_div);
        prepareForm();
        prepareDialog();
    });
    
    function launchCopy(ids) {
        confirmDialog('Copy', 'Are you sure you want to copy this '+name, function() {
            $.post(script, {event: 'datarecord_copy', ids: JSON.stringify(ids), __class: classname}, function(data) {
                // Reload tabulator
                table.replaceData();
            })
        })
    }
    
    function launchDelete(ids) {
        confirmDialog('Delete '+name, 'You are about to delete the selected '+name+'(s)', function() {
            $.post(script, {event: 'datarecord_delete', ids: JSON.stringify(ids), __class: classname}, function(data) {
                if (data.status == 0) {
                    warningDialog('Could not delete data', 'Could not delete '+name+'(s). Error was: '+data.errormessage);
                }
                if (table) {
                    table.replaceData();
                } 
            }, 'json');
        })        
    }
    
    function launchEdit(id) {
        form.clearForm();
        if (id) {
            // Load values
            form.loadValues(script, {event: 'datarecord_load', id: id, __class: classname}, function() {
                $(dialog).dialog('option', 'title', 'Edit '+name).dialog('open');
            });                  
        } else {
            $(dialog).dialog('option', 'title', 'New '+name).dialog('open');
        }
        dialog.dialog('open');
    }
    
    function prepareDialog() {
        dialog.on('save', function() {
            form.submit();
        })
    }
    
    function prepareForm() {
        form.find('input[name="form_event"]').val('datarecord_save');
        form.prepend('<input type="hidden" name="__class" value="'+short_classname+'">');
        form.submit(function() {
            $(form).find('[name="__class"]').val(classname);
            $.post(script, form.serialize(), function(data) {
                if (data.status) {
                    if (table) {
                        table.replaceData();
                    } 
                    $(dialog).dialog('close');
                } else {
                    add_errors_to_form(form, data.errors);
                }
            }, 'json');
            return false;
        })
    }

    function prepareMenu(menu) {
        $('#'+short_classname+'_new_button', menu).click(function() {
            launchEdit(0);
            return false;
        })
        $('#'+short_classname+'_edit_button', menu).click(function() {
            var ids = [];
            $.each(table.getSelectedRows(), function(i, elements) {
              ids.push(elements._row.data.id);
            });
            if (ids.length != 1) 
                warningDialog('Select one', 'You need to select exactly one '+name);
            else {
                launchEdit(ids.pop());
            }
            return false;
        });
        $('#'+short_classname+'_delete_button', menu).click(function() {
            var ids = [];
            $.each(table.getSelectedRows(), function(i, elements) {
              ids.push(elements._row.data.id);
            });
            launchDelete(ids);
            return false;
        });
        $('#'+short_classname+'_copy_button', menu).click(function() {
            var ids = [];
            $.each(table.getSelectedRows(), function(i, elements) {
              ids.push(elements._row.data.id);
            });
            if (! ids.length)
                warningDialog('Select at least one', 'You need to select at least one '+name);
            else {
                launchCopy(ids);
            }
        });
        $('#'+short_classname+'_column_select_button', menu).click(function() {
            column_select_dialog.dialog('open');
            return false;
        });
        
    }
    
    function prepareTable(table) {
        // Additional data rows
        table.addColumn({
            formatter: function(cell, formatterParams) {
                if (cell.getValue() == 1)
                    return '<i class="fa fa-trash"></i>';
                else
                    return '';
            },
            field: 'platform_can_delete',
            width: 40,
            headerSort:false,
            align: 'center',
            cellClick: function(e, cell) {
                if (cell.getValue() != 1) return;
                launchDelete([cell.getRow().getIndex()]);
            }
        }, false, 'checkboxcolumn');
        if ($('#'+short_classname+'_copy_button').length) {
            table.addColumn({
                formatter: function(cell, formatterParams) {
                    if (cell.getValue() == 1)
                        return '<i class="fa fa-plus"></i>';
                    else
                        return '';
                },
                field: 'platform_can_copy',
                width: 40,
                headerSort:false,
                align: 'center',
                cellClick: function(e, cell) {
                    if (cell.getValue() != 1) return;
                    launchCopy([cell.getRow().getIndex()]);
                }
            }, false, 'checkboxcolumn');

        }
        table.addColumn({
            formatter: function(cell, formatterParams) {
                if (cell.getValue() == 1)
                    return '<i class="fa fa-pencil"></i>';
                else
                    return '';
            },
            field: 'platform_can_edit',
            width: 40,
            headerSort:false,
            align: 'center',
            cellClick: function(e, cell) {
                if (cell.getValue() != 1) return;
                launchEdit(cell.getRow().getIndex());
            }
        }, false, 'checkboxcolumn');
    };
});