addCustomPlatformFunction(function(item) {
    item.find('.platform_component').each(function() {
        var element = $(this);
        $(this).on('disable', function(e) {
            $(this).greyOut(true);
            e.stopPropagation();
        });
        
        $(this).on('enable', function(e) {
            $(this).greyOut(false);
            e.stopPropagation();
        });
        
        $(this).on('disable_others', function(e) {
            console.log('Disable others triggered!');
            $('.platform_component.platform_component_candisable').not(element).trigger('disable');
            e.stopPropagation();
        });
        
        $(this).on('enable_others', function(e) {
            $('.platform_component.platform_component_candisable').not(element).trigger('enable');
            e.stopPropagation();
        });
    })
})
