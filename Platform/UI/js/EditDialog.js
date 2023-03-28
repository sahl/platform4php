addPlatformComponentHandlerFunction('editdialog', function(element) {
    
    var form = element.find('form');
    
    var name = element.data('element_name');
    
    element.on('new', function(event, values) {
        openDialog(0, values);
        return false;
    });
    
    element.on('edit', function(e, id) {
        openDialog(id);
        return false;
    })
    
    element.componentIOForm(form, function(data) {
        element.dialog('close');
        element.trigger('aftersave', data);
    });
    
    
    element.on('save', function(e) {
        form.submit();
    })
    
    function openDialog(id, values) {
        form.clearForm();
        element.componentIO({event: 'datarecord_load', id: id}, function(data) {
            if (data.status) {
                if (id) {
                    $(element).dialog('option', 'title', platform_TranslateForUser('Edit')+' '+name).dialog('open');
                } else {
                    $(element).dialog('option', 'title', platform_TranslateForUser('New')+' '+name).dialog('open');
                }
                form.attachValues(data.values);
                
                if (typeof values == 'object')
                    form.attachValues(values);
                element.dialog('open');
            } else {
                warningDialog(platform_TranslateForUser('Cannot edit'), platform_TranslateForUser('You cannot edit this element: %1',data.error));
            }
        });
    }

});
