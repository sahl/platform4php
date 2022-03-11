addPlatformComponentHandlerFunction('editcomplex', function(element) {

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
        if (table_div.data('inline_icons') == 1) prepareTable(table);
        prepareMenu(menu_div);
        prepareForm();
        prepareDialog();
        return false;
    });
    
    element.on('create_new', function() {
        launchEdit(0);
        return false;
    })
    
    element.on('copy', function(e, ids) {
        launchCopy(ids.split(','));
        return false;
    });
    
    element.on('edit', function(e, ids) {
        launchEdit(ids);
        return false;
    });

    element.on('delete', function(e, ids) {
        launchDelete(ids.split(','));
        return false;
    });
    
    element.on('columns', function(e, ids) {
        column_select_dialog.dialog('open');
        return false;
    });
    
    function launchCopy(ids) {
        confirmDialog('Copy', 'Are you sure you want to copy this '+name, function() {
            element.componentIO({event: 'datarecord_copy', ids: JSON.stringify(ids)}, function(data) {
                // Reload tabulator
                table_div.trigger('reload_data');
            })
        })
    }
    
    function launchDelete(ids) {
        confirmDialog('Delete '+name, 'You are about to delete the selected '+name+'(s)', function() {
            element.componentIO({event: 'datarecord_delete', ids: JSON.stringify(ids)}, function(data) {
                if (data.status == 0) {
                    warningDialog('Could not delete data', 'Could not delete '+name+'(s). Error was: '+data.errormessage);
                }
                if (table) {
                    table_div.trigger('reload_data');
                } 
            }, 'json');
        })        
    }
    
    function launchEdit(id) {
        form.clearForm();
        // Load values
        element.componentIO({event: 'datarecord_load', id: id}, function(data) {
            if (data.status == 1) {
                form.attachValues(data.data);
                form.trigger('dataloaded');
                if (id) {
                    $(dialog).dialog('option', 'title', 'Edit '+name).dialog('open');
                } else {
                    $(dialog).dialog('option', 'title', 'New '+name).dialog('open');
                    // Find default values (if any)
                    form.attachValues(JSON.parse(element.find('.default_values').html()));
                }
                dialog.dialog('open');
            }
        })
    }
    
    function prepareDialog() {
        dialog.on('save', function() {
            form.submit();
        })
    }
    
    function prepareForm() {
        form.find('input[name="form_event"]').val('datarecord_save');
        element.componentIOForm(form, function(data) {
            if (data.status) {
                if (table) {
                    table_div.trigger('reload_data');
                } 
                $(dialog).dialog('close');
            } else {
                add_errors_to_form(form, data.errors);
            }
        });
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
        table.on('tableBuilt', function() {
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
                hozAlign: 'center',
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
                    hozAlign: 'center',
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
                hozAlign: 'center',
                cellClick: function(e, cell) {
                    if (cell.getValue() != 1) return;
                    launchEdit(cell.getRow().getIndex());
                }
            }, false, 'checkboxcolumn');
            
        })
    };
});