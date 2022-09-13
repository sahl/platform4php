var platform_component_handler_functions = [];
var platform_component_handler_class_names = [];

addCustomPlatformFunctionLast(function(item) {
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
                element.on(value, function(event, parameter1, parameter2, parameter3) {
                    if (parameter1 == '__data_request_event') {
                        // This is a datatable event, which needs to be handled another way
                        parameter2['event'] = value;
                        element.componentIO(parameter2, function(data) {
                            parameter3(data);
                        }); 
                    } else {
                        element.componentIO({event: value, parameter1: parameter1, parameter2: parameter2, parameter3: parameter3 }); 
                        event.stopImmediatePropagation(); 
                    }
                    return false;
                });
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
    var item = this;
    $(form).submit(function() {
        item.componentIO(form.serialize(), function(data) {
            if (! data.status) {
                form.attachErrors(data.form_errors);
            } else {
                if (typeof func == 'function') func(data);
            }
        })
        return false;
    })
}

var communication_stack = [];
var communication_timer = null;

$.fn.componentTimedIO = function(values, callback, polltime, precision) {
    var component = this;
    var object = {};
    // Prepare communication object
    object.values = values;
    object.callback = callback;
    object.polltime = polltime;
    object.precision = precision;
    object.component = component;
    object.componentclass = component.data('componentclass');
    object.componentproperties = component.data('componentproperties');
    object.componentid = component.prop('id');
    object.timeleft = polltime;
    

    // Add or replace to queue
    var inserted = false;
    $.each(communication_stack, function(id, element) {
        if (element.component == component) {
            communication_stack[id] = object;
            inserted = true;
            return false;
        }
        return true;
    });
    if (! inserted) communication_stack.push(object);
    
    if (! communication_timer) communication_timer = setTimeout(platformTimedIO, 1000);
}

$.fn.componentRemoveTimedIO = function() {
    var component = this;
    $.each(communication_stack, function(id, element) {
        if (element.component == component) {
            communication_stack.splice(id,1);
            return false;
        }
        return true;
    });
    if (! communication_stack.length) {
        clearTimeout(communication_timer);
        comminication_timer = null;
    }
}

function platformTimedIO() {
    console.log('Timed function running');
    // Decrease everything and find if something needs to run now
    var run_now = false;
    $.each(communication_stack, function(id, element) {
        element.timeleft -= 1;
        if (element.timeleft < 1) {
            run_now = true;
        }
        return true;
    });
    if (run_now) {
        // We need to run now
        var run_payload = [];
        var callbacks = [];
        var url = null;
        $.each(communication_stack, function(id, element) {
            if (element.timeleft - element.precision <= 0) {
                var payload = {};
                if (! url) url = element.component.data('io_url')
                payload.componentclass = element.componentclass;
                payload.componentproperties = element.componentproperties;
                payload.componentid = element.componentid;
                payload.values = element.values;
                run_payload.push(payload);
                callbacks.push(element.callback);
                // Reset element
                element.timeleft += element.polltime;
            }
        });
        // Call it
        var final_payload = {event: '__timedio', payloads: run_payload};
        $.post(url, final_payload, function(data) {
            console.log('Timed function calling in');
            $.each(data, function(id, return_value) {
                if (callbacks[id]) callbacks[id](return_value);
            })
            communication_timer = setTimeout(platformTimedIO, 1000);
        }, 'json');
        // Rearm
    } else {
        if (communication_stack.length) communication_timer = setTimeout(platformTimedIO, 1000);
        
    }
}

$.fn.componentIO = function(values, func) {
    var component = this;
    // This only works on components.
    if (! component.hasClass('platform_component')) return;
    
    // Values can be an object or a serialized string
    if (typeof values == 'string') {
        // It is a serialized string

        // Inject class field
        values += '&componentclass='+encodeURIComponent(component.data('componentclass'));
        // Inject properties    
        values += '&componentproperties='+encodeURIComponent(component.data('componentproperties'));
        // Inject ID
        values += '&componentid='+encodeURIComponent(component.prop('id'));
    } else {
        // It is an array/object

        // Inject class field
        values['componentclass'] = component.data('componentclass');
        // Inject properties
        values['componentproperties'] = component.data('componentproperties');
        // Inject ID
        values['componentid'] = component.prop('id');
    }
    
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