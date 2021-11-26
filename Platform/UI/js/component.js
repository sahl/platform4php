var platform_component_handler_functions = [];
var platform_component_handler_class_names = [];

addCustomPlatformFunction(function(item) {
    var elements = item.find('.platform_component');
    if (item.hasClass('platform_component')) if (elements.length) elements = elements.add(item); else elements = item;
    
    // General functions on elements
    elements.each(function() {
        var element = $(this);
        
        // Remove events if they exists
        $(this).off('redraw');
        
        // Gather dialog ID's within this component
        var dialogs = [];
        $('.platform_component_dialog', this).each(function() {
            dialogs.push($(this).prop('id'));
            return true;
        });
        
        $(this).on('redraw', function(e) {
            var componentproperties = $(this).data('componentproperties');
            var redraw_url = $(this).data('redraw_url');
            if (! redraw_url) {
                e.stopPropagation();
                return false;
            }
            
            // Destroy all dialogs within this component
            $.each(dialogs, function(index, value) {
                $('#'+value).dialog('destroy');
            })
            
            var element = $(this);
            $.post(redraw_url, {componentclass: $(this).data('componentclass'), componentproperties: componentproperties, componentid: $(this).prop('id')}, function(data) {
                // Destroy handlers
                element.off();
                element.html(data).applyPlatformFunctions();
            });
            e.stopPropagation();
        });
        
        // Pass custom events to backend
        if (element.data('registered_events')) {
            $.each(element.data('registered_events').split(','), function(index, value) {
                element.off(value);
                element.on(value, function(event, parameter1, parameter2, parameter3) {element.componentIO({event: value, parameter1: parameter1, parameter2: parameter2, parameter3: parameter3 }); event.stopImmediatePropagation(); return false;});
            })
        }
        
    });
    
    // Apply special functions in the same order they was included to ensure proper event stacking
    $.each(platform_component_handler_functions, function(key, array_element) {
        elements.each(function() {
            var element = $(this);
            if (element.hasClass('platform_component_'+array_element.class_name)) {
                array_element.func(element);
            }
        });
    });
    
    
    // Fire component ready on all
    elements.each(function() {
        var element = $(this);
        
        // Check if a form is registered for IO
        if (element.data('attached_form_id')) {
            element.componentIOForm($('#'+element.data('attached_form_id'), element))
        };
        $(this).triggerHandler('component_ready');
    });
});

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
        if (! form.find('input[name="componentclass"]').length) form.append('<input type="hidden" name="componentclass">');
        form.find('input[name="componentclass"]').val(component.data('componentclass'));
        // Inject properties field if not present
        if (! form.find('input[name="componentproperties"]').length) form.append('<input type="hidden" name="componentproperties">');
        form.find('input[name="componentproperties"]').val(component.data('componentproperties'));
        // Inject id field if not present
        if (! form.find('input[name="componentid"]').length) form.append('<input type="hidden" name="componentid">');
        form.find('input[name="componentid"]').val(component.prop('id'));
        // Post
        $.post(component.data('io_url'), form.serialize(), function(data) {
            // Handle form error
            if (! data.status) {
                if (data.destroy) component.remove();
                if (data.script) eval(data.script);
                if (data.properties) component.data('componentproperties', data.properties);
                if (data.data) {
                    $.each(data.data, function(i, v) {
                        component.data(i,v);
                    });
                }
                if (data.trigger) component.trigger(data.trigger);
                if (data.redraw) component.trigger('redraw');
                form.attachErrors(data.form_errors);
                if (typeof func == 'function') func(data);
            } else {
                if (data.destroy) component.remove();
                if (data.script) eval(data.script);
                if (data.redirect) location.href = data.redirect;
                if (data.properties) component.data('componentproperties', data.properties);
                if (data.data) {
                    $.each(data.data, function(i, v) {
                        component.data(i,v);
                    });
                }
                if (data.trigger) component.trigger(data.trigger);
                if (data.redraw) component.trigger('redraw');
                if (typeof func == 'function') func(data);
            }
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
    // Inject properties
    values['componentproperties'] = component.data('componentproperties');
    // Inject ID
    values['componentid'] = component.prop('id');
    // Post
    $.post(component.data('io_url'), values, function(data) {
        if (data.destroy) component.remove();
        if (data.script) eval(data.script);
        if (data.redirect) location.href = data.redirect;
        if (data.properties) component.data('componentproperties', data.properties);
        if (data.data) {
            $.each(data.data, function(i, v) {
                component.data(i,v);
            });
        }
        if (data.trigger) component.trigger(data.trigger);
        if (data.redraw) component.trigger('redraw');
        if (typeof func == 'function') func(data);
    }, 'json');
}