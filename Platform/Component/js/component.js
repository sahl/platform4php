var platform_component_handler_functions = [];
var platform_component_handler_class_names = [];

addCustomPlatformFunction(function(item) {
    var elements = item.find('.platform_component');
    if (item.hasClass('platform_component')) if (elements.length) elements = elements.add(item); else elements = item;
    elements.each(function() {
        var element = $(this);
        
        // Remove events if they exists
        $(this).off('redraw');
        
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
            if (element.hasClass('platform_component_'+array_element.class_name)) {
                array_element.func(element);
            }
        });
        
        $(this).trigger('component_ready');
    })
})

function addPlatformComponentHandlerFunction(class_name, func) {
    // Ensure we only add everything once.
    if (platform_component_handler_class_names.includes(class_name)) return;
    
    var handler_element = {
        class_name: class_name,
        func: func
    };
    platform_component_handler_functions.push(handler_element);
    platform_component_handler_class_names.push(class_name);
}