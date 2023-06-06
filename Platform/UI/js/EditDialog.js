Platform.EditDialog = class extends Platform.Dialog {
    
    form = null;
    
    name = '';
    
    constructor(dom_node) {
        super(dom_node);
    }
    
    initialize() {
        super.initialize();
        this.form = this.dom_node.find('.platform_component_form').platformComponent();
        this.name = this.dom_node.data('element_name');

        var component = this;
        this.dom_node.on('new', function(event, values) {
            component.openDialog(0, values);
            return false;
        });

        this.dom_node.on('edit', function(e, id) {
            component.openDialog(id);
            return false;
        })

        this.addIOForm(this.form.dom_node.find('form'), function(data) {
            component.dom_node.dialog('close');
            component.dom_node.trigger('aftersave', data);
        });

        this.dom_node.on('save', function(e) {
            component.form.submit();
        })
    }
    
    openDialog(id, values) {
        var component = this;
        this.form.clear();
        component.backendIO({event: 'datarecord_load', id: id}, function(data) {
            if (data.status) {
                if (id) {
                    component.dom_node.dialog('option', 'title', Platform.Translation.forUser('Edit')+' '+name).dialog('open');
                } else {
                    component.dom_node.dialog('option', 'title', Platform.Translation.forUser('New')+' '+name).dialog('open');
                }
                component.form.attachValues(data.values);
                
                if (typeof values == 'object')
                    form.attachValues(values);
                console.log('Fire open');
                component.dom_node.dialog('open');
            } else {
                Dialog.warningDialog(Platform.Translation.forUser('Cannot edit'), Platform.Translation.forUser('You cannot edit this element: %1',data.error));
            }
        });
    }
    
}

Platform.Component.bindClass('platform_editdialog', Platform.EditDialog);