addPlatformComponentHandlerFunction('editdialog', function(element) {
    
    var form = element.find('form');
    
    var script = element.data('io_datarecord');
    
    var classname = element.data('classname');
    
    var name = element.data('element_name');
    
    prepareForm();
    
    element.on('new', function(e) {
        openDialog(0);
        return false;
    });
    
    element.on('edit', function(e, id) {
        openDialog(id);
        return false;
    })
    
    element.on('save', function(e) {
        form.submit();
    })
    
    function openDialog(id) {
        form.clearForm();
        form.loadValues(script, {event: 'datarecord_load', id: id, __class: classname}, function() {
            if (id) {
                $(element).dialog('option', 'title', 'Edit '+name).dialog('open');
            } else {
                $(element).dialog('option', 'title', 'New '+name).dialog('open');
            }
        });                  
        element.dialog('open');
    }
    
    function prepareForm() {
        form.find('input[name="form_event"]').val('datarecord_save');
        form.prepend('<input type="hidden" name="__class">');
        form.submit(function() {
            $(form).find('[name="__class"]').val(classname);
            $.post(script, form.serialize(), function(data) {
                if (data.status) {
                    element.dialog('close');
                    element.trigger('aftersave');
                } else {
                    add_errors_to_form(form, data.errors);
                }
            }, 'json');
            return false;
        })
    }    

});