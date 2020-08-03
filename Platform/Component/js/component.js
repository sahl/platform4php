var platform_component_handler_functions = [];

addCustomPlatformFunction(function(item) {
    var elements = item.find('.platform_component');
    if (item.hasClass('platform_component')) if (elements.length) elements.add(item); else elements = item;
    elements.each(function() {
        var element = $(this);
        
        // Remove events if they exists
        $(this).off('disable enable disable_others enable_others redraw');
        
        $(this).on('disable', function(e) {
            $(this).greyOut(true);
            e.stopPropagation();
        });
        
        $(this).on('enable', function(e) {
            $(this).greyOut(false);
            e.stopPropagation();
        });
        
        $(this).on('disable_others', function(e) {
            $('.platform_component.platform_component_candisable').not(element).trigger('disable');
            e.stopPropagation();
        });
        
        $(this).on('enable_others', function(e) {
            $('.platform_component.platform_component_candisable').not(element).trigger('enable');
            e.stopPropagation();
        });
        
        $(this).on('redraw', function(e) {
            var componentproperties = $(this).data('componentproperties');
            var redraw_url = $(this).data('redraw_url');
            if (! redraw_url) {
                e.stopPropagation();
                return;
            }
            var element = $(this);
            $.post(redraw_url, {componentclass: $(this).data('componentclass'), componentproperties: componentproperties}, function(data) {
                element.html(data).applyPlatformFunctions();
            });
            e.stopPropagation();
        })

        $.each(platform_component_handler_functions, function(key, array_element) {
            if (element.hasClass('platform_component_'+array_element.class_name)) array_element.func(element);
        });
        
        $(this).trigger('component_ready');
    })
})

function addPlatformComponentHandlerFunction(class_name, func) {
    var handler_element = {
        class_name: class_name,
        func: func
    };
    platform_component_handler_functions.push(handler_element);
}