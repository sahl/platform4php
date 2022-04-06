addPlatformComponentHandlerFunction('editcomplex', function(element) {
    
    // Data object title
    var name = element.data('name');
    
    // My ID
    var id = element.prop('id');
    
    // Short class name
    var shortclass = element.data('shortclass');
    
    // The edit dialog
    var edit_dialog = element.find('#'+shortclass+'_edit_dialog');
    
    // The column select dialog
    var column_select_dialog = element.find('#'+id+'_table_component_select');
    
    // The table div
    var table_div = $('#'+id+'_table');

    // Reload table data when edit dialog saves
    edit_dialog.on('aftersave', function() {
        table_div.trigger('reload_data');
    })

    // Create new item
    element.on('create_object', function() {
        edit_dialog.trigger('new');
        return false;
    })
    
    element.on('copy_objects', function(event) {
        var ids = $(event.target).closest('.platform_component_popupmenu').data('platform_info');
        if (ids.length < 1) warningDialog('Cannot copy', 'You must select at least one item to copy.');
        else 
            confirmDialog('Copy', 'Are you sure you want to copy the selected '+name, function() {
                element.componentIO({event: 'datarecord_copy', ids: ids}, function(data) {
                    // Reload tabulator
                    table_div.trigger('reload_data');
                })
            })
        return false;
    });
    
    element.on('copy_object', function(event) {
        var ids = $(event.target).closest('.platform_component_popupmenu').data('platform_info');
        confirmDialog('Copy', 'Are you sure you want to copy this '+name, function() {
            element.componentIO({event: 'datarecord_copy', ids: ids}, function(data) {
                // Reload tabulator
                table_div.trigger('reload_data');
            })
        })
        return false;
    });
    
    element.on('edit_objects', function(event) {
        var ids = $(event.target).closest('.platform_component_popupmenu').data('platform_info');
        if (ids.length != 1) warningDialog('Cannot edit', 'You need to select exactly one element to edit.');
        else edit_dialog.trigger('edit', ids[0]);
        return false;
    })
    
    element.on('edit_object', function(event) {
        var id = $(event.target).closest('.platform_component_popupmenu').data('platform_info');
        edit_dialog.trigger('edit', id[0]);
        return false;
    });

    element.on('delete_objects', function(event) {
        var ids = $(event.target).closest('.platform_component_popupmenu').data('platform_info');
        confirmDialog('Delete', 'Are you sure you want to delete the selected '+name, function() {
            element.componentIO({event: 'datarecord_delete', ids: ids}, function(data) {
                if (data.status == 0) {
                    warningDialog('Could not delete data', 'Could not delete '+name+'(s). Error was: '+data.errormessage);
                }
                // Reload tabulator
                table_div.trigger('reload_data');
            })
        })
        return false;
    });
    
    element.on('delete_object', function(event) {
        var ids = $(event.target).closest('.platform_component_popupmenu').data('platform_info');
        confirmDialog('Delete', 'Are you sure you want to delete this '+name, function() {
            element.componentIO({event: 'datarecord_delete', ids: ids}, function(data) {
                // Reload tabulator
                table_div.trigger('reload_data');
            })
        })
        return false;
    });

    element.on('select_columns', function(event) {
        column_select_dialog.trigger('open');
        return false;
    });
});