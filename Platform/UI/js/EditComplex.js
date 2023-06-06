Platform.EditComplex = class extends Platform.Component {
    
    name = '';
    
    id = '';
    
    shortclass = '';
    
    edit_dialog_dom = null;
    
    column_select_dialog_dom = null;
    
    table = null;
    
    initialize() {
        var component = this;
        
        // Data object title
        this.name = this.dom_node.data('name');

        // My ID
        this.id = this.dom_node.prop('id');

        // Short class name
        this.shortclass = this.dom_node.data('shortclass');

        // The edit dialog
        this.edit_dialog = this.dom_node.find('#'+this.shortclass+'_edit_dialog');

        // The column select dialog
        this.column_select_dialog = this.dom_node.find('#'+this.id+'_table_component_select');

        // The table
        this.table_div = $('#'+this.id+'_table');

        // Reload table data when edit dialog saves
        component.edit_dialog.on('aftersave', function() {
            component.table_div.platformComponent().loadData();
        })

        // Create new item
        component.dom_node.on('create_object', function() {
            component.edit_dialog.trigger('new');
            return false;
        })

        component.dom_node.on('copy_objects', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            if (ids.length < 1) Platform.Dialog.warningDialog('Cannot copy', 'You must select at least one item to copy.');
            else 
                Platform.Dialog.confirmDialog('Copy', 'Are you sure you want to copy the selected '+name, function() {
                    component.backendIO({event: 'datarecord_copy', ids: ids}, function(data) {
                        // Reload tabulator
                        component.table_div.platformComponent().loadData();
                    })
                })
            return false;
        });

        component.dom_node.on('copy_object', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            Platform.Dialog.confirmDialog('Copy', 'Are you sure you want to copy this '+name, function() {
                component.backendIO({event: 'datarecord_copy', ids: ids}, function(data) {
                    // Reload tabulator
                    component.table_div.platformComponent().loadData();
                })
            })
            return false;
        });

        component.dom_node.on('edit_objects', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            if (ids.length != 1) Platform.Dialog.warningDialog('Cannot edit', 'You need to select exactly one element to edit.');
            else component.edit_dialog.trigger('edit', ids[0]);
            return false;
        })

        component.dom_node.on('edit_object', function(event) {
            var id = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            component.edit_dialog.trigger('edit', id[0]);
            return false;
        });

        component.dom_node.on('delete_objects', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            Platform.Dialog.confirmDialog('Delete', 'Are you sure you want to delete the selected '+name, function() {
                component.backendIO({event: 'datarecord_delete', ids: ids}, function(data) {
                    if (data.status == 0) {
                        Platform.Dialog.warningDialog('Could not delete data', 'Could not delete '+name+'(s). Error was: '+data.errormessage);
                    }
                    // Reload tabulator
                    component.table_div.platformComponent().loadData();
                })
            })
            return false;
        });

        component.dom_node.on('delete_object', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            Platform.Dialog.confirmDialog('Delete', 'Are you sure you want to delete this '+name, function() {
                component.backendIO({event: 'datarecord_delete', ids: ids}, function(data) {
                    // Reload tabulator
                    component.table_div.platformComponent().loadData();
                })
            })
            return false;
        });

        component.dom_node.on('select_columns', function(event) {
            component.column_select_dialog.trigger('open');
            return false;
        });
        
    }
}

Platform.Component.bindClass('platform_editcomplex', Platform.EditComplex);
