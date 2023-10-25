Platform.ColumnSelector = class extends Platform.Component {
    
    dialog = null;
    
    attached_table = null;
    
    form = null;
    
    initialize() {
        var component = this;
        
        this.dialog = $('#'+this.dom_node.prop('id')+'_dialog').platformComponent();
        
        this.attached_table = $('#'+this.dom_node.data('table_id')+'_table').platformComponent();
        
        this.form = this.dialog.dom_node.find('form').platformComponent();

        this.form.dom_node.submit(function() {
            // Implement it
            var id = $(this).find('input[name="table_id"]').val();
            var visible = {};
            $(this).find('input[name="fields[]"]').each(function() {
                if ($(this).is(':checked')) component.attached_table.tabulator.showColumn($(this).val());
                else component.attached_table.tabulator.hideColumn($(this).val());
                visible[$(this).val()] = $(this).is(':checked') ? 1 : 0;
            });
            // Save it
            component.backendIO({event: 'savevisibility', id: component.dom_node.data('table_id'), visible: visible});
            return false;
        });
        
        
        this.dialog.dom_node.on('save_columns', function() {
            // Do this to make sure we have a saved configuration with the current layout
            component.attached_table.saveTableLayout();
            component.form.submit();
            component.dialog.close();
        });
    
        this.dialog.dom_node.on('reset_columns', function() {
            component.backendIO({event: 'reset_columns', id: component.dom_node.data('table_id')});
            component.dialog.close();
        });
        
        this.dialog.dom_node.on('close', function() {
            component.dialog.close();
        })
    }
    
    open() {
        this.dialog.open();
    }    
    
}

Platform.Component.bindClass('platform_component_column_selector', Platform.ColumnSelector);
