addPlatformComponentHandlerFunction('tablecolumnselector', function(item) {
    var attached_table = Tabulator.findTable('#'+item.data('table_id'))[0];
    
    var dialog = $('#'+item.prop('id')+'_dialog');
    var form = dialog.find('form');
    
    form.submit(function() {
        // Implement it
        var id = $(this).find('input[name="table_id"]').val();
        var visible = {};
        $(this).find('input[name="fields[]"]').each(function() {
            if ($(this).is(':checked')) attached_table.showColumn($(this).val());
            else attached_table.hideColumn($(this).val());
            visible[$(this).val()] = $(this).is(':checked') ? 1 : 0;
        });
        // Save it
        item.componentIO({action: 'savevisibility', id: item.data('table_id'), visible: visible});
        return false;
    });
    
    item.on('open', function() {
        dialog.dialog('open');
    })
    
    dialog.on('save_columns', function() {
        form.submit();
        $(this).dialog('close');
    });
});
