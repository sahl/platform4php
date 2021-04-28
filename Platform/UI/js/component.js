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

$.fn.componentIOForm = function(form, func) {
    var component = this;
    // This only works on components.
    if (! component.hasClass('platform_component')) return;
    // Add custom submit function
    form.submit(function() {
        // Inject class field if not present
        if (! form.find('input[name="componentclass"]').length) form.append('<input type="hidden" name="componentclass" value="'+component.data('componentclass')+'">');
        // Post
        $.post(component.data('io_url'), form.serialize(), function(data) {
            // Handle form error
            if (! data.status) {
                form.attachErrors(data.form_errors);
            } else if (typeof func == 'function') func(data);
        }, 'json')
        return false;
    })
}

$.fn.componentIO = function(values, func) {
    var component = this;
    // This only works on components.
    if (! component.hasClass('platform_component')) return;
    // Inject class field
    values['componentclass'] = component.data('componentclass');
    // Post
    $.post(component.data('io_url'), values, function(data) {
        if (typeof func == 'function') func(data);
    }, 'json');
}