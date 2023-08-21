Platform.EditComplex = class extends Platform.Component {
    
    name = '';
    
    id = '';
    
    shortclass = '';
    
    edit_dialog = null;
    
    edit_dialog_dom = null;
    
    column_select_dialog_dom = null;
    
    table = null;
    
    new_item_values = null;
    
    initialize() {
        var component = this;
        
        // Data object title
        this.name = this.dom_node.data('name');

        // My ID
        this.id = this.dom_node.prop('id');

        // Short class name
        this.shortclass = this.dom_node.data('shortclass');

        // The edit dialog
        this.edit_dialog_dom = $('#'+this.shortclass+'_edit_dialog');
        
        // The edit dialog javascript object
        this.edit_dialog = this.edit_dialog_dom.platformComponent();

        // The column select dialog
        this.column_select_dialog = $('#'+this.id+'_table_component_select');

        // The table
        this.table_div = $('#'+this.id+'_table');

        // Reload table data when edit dialog saves
        component.edit_dialog_dom.on('aftersave', function() {
            component.table_div.platformComponent().loadData();
        })
        
        // Load new item values from data if any
        var new_item_values = component.dom_node.data('new_item_values');
        if (new_item_values) this.setNewItemValues(new_item_values);
        
        // Create new item
        component.dom_node.on('create_object', function() {
            component.edit_dialog.openDialog(0, component.new_item_values);
            return false;
        })

        component.dom_node.on('copy_objects', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            if (ids.length < 1) Platform.Dialog.warningDialog('Cannot copy', 'You must select at least one item to copy.');
            else 
                Platform.Dialog.confirmDialog('Copy', 'Are you sure you want to copy the selected '+component.name, function() {
                    component.backendIO({event: 'datarecord_copy', ids: ids}, function(data) {
                        // Reload tabulator
                        component.table_div.platformComponent().loadData();
                    })
                })
            return false;
        });

        component.dom_node.on('copy_object', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            Platform.Dialog.confirmDialog('Copy', 'Are you sure you want to copy this '+component.name, function() {
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
            else component.edit_dialog.openDialog(ids[0]);
            return false;
        })

        component.dom_node.on('edit_object', function(event) {
            var id = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            component.edit_dialog.openDialog(id[0]);
            return false;
        });

        component.dom_node.on('delete_objects', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            Platform.Dialog.confirmDialog('Delete', 'Are you sure you want to delete the selected '+component.name, function() {
                component.backendIO({event: 'datarecord_delete', ids: ids}, function(data) {
                    if (data.status == 0) {
                        Platform.Dialog.warningDialog('Could not delete data', 'Could not delete '+component.name+'(s). Error was: '+data.errormessage);
                    }
                    // Reload tabulator
                    component.table_div.platformComponent().loadData();
                })
            })
            return false;
        });

        component.dom_node.on('delete_object', function(event) {
            var ids = $(event.target).closest('.platform_menu_popupmenu').data('platform_info');
            Platform.Dialog.confirmDialog('Delete', 'Are you sure you want to delete this '+component.name, function() {
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
    
    setNewItemValues(values) {
        this.new_item_values = values;
    }
}

Platform.Component.bindClass('platform_editcomplex', Platform.EditComplex);
