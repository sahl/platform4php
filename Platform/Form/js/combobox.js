addCustomPlatformFunction(function(item) {
    $('.platform_combobox', item).each(function() {
        var element = $(this);
        console.log('Autocomplete to '+element.prop('id')+' with source '+element.data('source'));
        // Destroy an already present autocomplete
        element.removeData('uiAutocomplete');
        element.autocomplete({
            source: element.data('source'),
            minLength: 2,
            select: function(event, ui) {
                if (element.hasClass('platform_indexed_combobox')) {
                    element.prev().val(ui.item.real_id);
                    element.data('validated_value', ui.item.value);
                }
            },
            change: function(event, ui) {
                if (element.hasClass('platform_indexed_combobox')) {
                    if (element.val() != element.data('validated_value')) $(this).prev().val('0');
                }
            }
        });
        return true;
    });
});

