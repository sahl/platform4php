addPlatformComponentHandlerFunction('editdialog', function(element) {
    
    var form = element.find('form');
    
    var name = element.data('element_name');
    
    element.on('new', function(e) {
        openDialog(0);
        return false;
    });
    
    element.on('edit', function(e, id) {
        openDialog(id);
        return false;
    })
    
    element.componentIOForm(form, function(data) {
        element.dialog('close');
        element.trigger('aftersave');
    });
    
    
    element.on('save', function(e) {
        form.submit();
    })
    
    function openDialog(id) {
        form.clearForm();
        element.componentIO({event: 'datarecord_load', id: id}, function(data) {
            if (data.status) {
                if (id) {
                    $(element).dialog('option', 'title', 'Edit '+name).dialog('open');
                } else {
                    $(element).dialog('option', 'title', 'New '+name).dialog('open');
                }
                form.attachValues(data.values);
                element.dialog('open');
            } else {
                warningDialog('Cannot edit', 'You cannot edit this element: '+data.error);
            }
        });
    }

});