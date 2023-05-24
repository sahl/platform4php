Platform.addCustomFunction(function(item) {
    $('.platform_combobox', item).each(function() {
        var element = $(this);
        // Destroy an already present autocomplete
        element.removeData('uiAutocomplete');
        var source = element.data('source');
        if (element.data('filter')) source += '&filter='+element.attr('data-filter');
        element.autocomplete({
            source: source,
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

