$(function() {
    $('.platform_render_edit_complex').each(function() {
        datarecord_list_edit_complex(
            $(this).data('name'), 
            $(this).data('class'),
            '#'+$(this).data('shortclass')+'_table',
            '#'+$(this).data('shortclass')+'_edit_dialog',
            '#'+$(this).data('shortclass')+'_column_dialog',
            '#'+$(this).data('shortclass')+'_new_button',
            '#'+$(this).data('shortclass')+'_edit_button',
            '#'+$(this).data('shortclass')+'_copy_button',
            '#'+$(this).data('shortclass')+'_delete_button',
            '#'+$(this).data('shortclass')+'_column_select_button'
        );
    })
})

function datarecord_list_edit_complex(name, classname, list_view, edit_dialog, column_dialog, create_button, edit_button, copy_button, delete_button, column_select_button) {
    var script = $(list_view).data('io_datarecord');
    var table = getTableByID(list_view);
    
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
            confirmDialog('Delete '+name, 'You are about to delete the selected '+name+'(s)', function() {
                $.post(script, {event: 'datarecord_delete', ids: JSON.stringify([cell.getRow().getIndex()]), __class: classname}, function(data) {
                    if (data.status == 0) {
                        warningDialog('Could not delete data', 'Could not delete '+name+'(s). Error was: '+data.errormessage);
                    }
                    if (table) {
                        table.replaceData();
                    } 
                }, 'json');
            })
        }
    }, false, 'checkboxcolumn');
    if ($(copy_button).length) {
        table.addColumn({
            formatter: function(cell, formatterParams) {
                if (cell.getValue() == 1)
                    return '<i class="fa fa-clone"></i>';
                else
                    return '';
            },
            field: 'platform_can_copy',
            width: 40,
            headerSort:false,
            align: 'center',
            cellClick: function(e, cell) {
                if (cell.getValue() != 1) return;
                confirmDialog('Copy', 'Are you sure you want to copy this '+name, function() {
                    $.post(script, {event: 'datarecord_copy', ids: JSON.stringify([cell.getRow().getIndex()]), __class: classname}, function(data) {
                        // Reload tabulator
                        table.replaceData();
                    })
                })
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
            $(form).clearForm();
            $(form).loadValues(script, {event: 'datarecord_load', id: cell.getRow().getIndex(), __class: classname}, function() {
                $(edit_dialog).dialog('option', 'title', 'Edit '+name).dialog('open');
            });        
        }
    }, false, 'checkboxcolumn');
    
    // Capture edit form
    var form = $(edit_dialog).find('form');
    // Set objective
    form.find('input[name="form_event"]').val('datarecord_save');
    form.prepend('<input type="hidden" name="__class" value="'+classname+'">');
    // Init dialogs
    $(edit_dialog).platformDialog([
        {
            text: 'Save',
            click: function() {
                form.submit();
            }
        },
        {
            text: 'Cancel',
            click: function() {
                $(this).dialog('close');
            }
        }
    ]);
    
    $(column_dialog).platformDialog([
        {
            text: 'Save',
            click: function() {
                var columnform = $(list_view+'_column_select_form');
                columnform.submit();
                $(this).dialog('close');
            }
        },
        {
            text: 'Cancel',
            click: function() {
                $(this).dialog('close');
            }
        }
    ]);
    
    
    // Init forms
    form.submit(function() {
        $(form).find('[name="__class"]').val(classname);
        $.post(script, form.serialize(), function(data) {
            if (data.status) {
                var table = getTableByID(list_view);
                if (table) {
                    table.replaceData();
                } 
                $(edit_dialog).dialog('close');
            } else {
                add_errors_to_form(form, data.errors);
            }
        }, 'json');
        return false;
    });
    
    $(create_button).click(function() {
        $(form).clearForm();
        $(form).loadValues(script, {event: 'datarecord_load', id: 0, __class: classname}, function() {
            $(edit_dialog).dialog('option', 'title', 'Create new '+name).dialog('open');
        });
    })
    
    $(edit_button).click(function() {
        var ids = getSelectedTableIds(list_view);
        if (ids.length != 1) 
            warningDialog('Select one', 'You need to select exactly one '+name);
        else {
            $(form).clearForm();
            $(form).loadValues(script, {event: 'datarecord_load', id: ids.pop(), __class: classname}, function() {
                $(edit_dialog).dialog('option', 'title', 'Edit '+name).dialog('open');
            });
        }
    })
    
    $(delete_button).click(function() {
        var ids = getSelectedTableIds(list_view);
        if (! ids.length)
            warningDialog('Select at least one', 'You need to select at least one '+name);
        else {
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
    })
    
    $(column_select_button).click(function() {
        $(column_dialog).dialog('open');
    })
}