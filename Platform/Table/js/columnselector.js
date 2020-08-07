addPlatformComponentHandlerFunction('tablecolumnselector', function(item) {
    var attached_table = getTableByID('#'+item.data('table_id'));
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
        $.post('/Platform/Table/php/save_table_properties.php', {action: 'savevisibility', id: item.data('table_id'), visible: visible});
        sizeTableContainer($('#'+item.data('table_id')));
        return false;
    });
    
    dialog.on('save_columns', function() {
        form.submit();
        $(this).dialog('close');
    });
});
