Platform.Component = class {
    
    handler_classes = [];
    
    dom_node = null;
    
    static dom_classes = [];
    
    static class_library = [];
    
    contained_dialogs = [];
    
    static addComponentClasses(selector) {
        $.each(Platform.Component.class_library, function(key, library_element) {
            selector.find('.'+library_element.dom_class).each(function() {
                var component = new library_element.javascript_class($(this));
            });
            if (selector.is('.'+library_element.dom_class)) var component = new library_element.javascript_class(selector);
        })
    }
    
    apply() {
        
    }
    
    constructor(dom_node) {
        this.dom_node = dom_node;
        this.gatherDialogs();
        this.apply();
        dom_node.data('platform_component', this);
    }
    
    static BindClass(dom_class, javascript_class) {
        // Ensure we only add everything once.
        if (Platform.Component.dom_classes.includes(dom_class)) return;

        var library_element = {
            dom_class: dom_class,
            javascript_class: javascript_class,
        }
        
        Platform.Component.dom_classes.push(dom_class);
        Platform.Component.class_library.push(library_element);
    }
    
    gatherDialogs() {
        $('.platform_component_dialog', this.dom_node).each(function() {
            this.contained_dialogs.push($(this).prop('id'));
            return true;
        })
    }
    
    redraw() {
        if (this.dom_node.is('.platform_container_component')) {
            // Redraw all subcomponents
            this.dom_node.find('.platform_component').each(function() {
                if ($(this).parent().closest('.platform_component')[0] == this.dom_node[0]) {
                    // Redraw subcomponent.
                }
            })
        } else {
            var componentproperties = this.dom_node.data('componentproperties');
            var redraw_url = this.dom_node.data('redraw_url');
            if (! redraw_url) {
                return false;
            }
            
            // Destroy all dialogs within this component
            this.contained_dialogs.forEach(value => function(value) {
                $('#'+value).dialog('destroy');
            })

            var component = this;
            
            $.post(redraw_url, {componentclass: this.dom_node.data('componentclass'), componentproperties: componentproperties, componentid: this.dom_node.prop('id')}, function(data) {
                // Destroy handlers
                Platform.apply(component.dom_node.html(data));
            });
            
        }
    }
    
    registerBackendEvents() {
        // Pass custom events to backend
        if (this.dom_node.data('registered_events')) {
            $.each(this.dom_node.data('registered_events').split(','), function(index, value) {
                this.dom_node.off(value);
                this.dom_node.on(value, function(event, parameter1, parameter2, parameter3) {
                    if (parameter1 == '__data_request_event') {
                        // This is a datatable event, which needs to be handled another way
                        parameter2['event'] = value;
                        this.backendIO(parameter2, function(data) {
                            parameter3(data);
                        }); 
                    } else {
                        this.backendIO({event: value, parameter1: parameter1, parameter2: parameter2, parameter3: parameter3 }); 
                    }
                    return false;
                });
            })
        }
    }
    
    addIOForm(form, func, failfunc) {
        var component = this;
        $(form).submit(function() {
            component.backendIO(form.serialize(), function(data) {
                if (! data.status) {
                    form.attachErrors(data.form_errors);
                    if (typeof failfunc == 'function') failfunc(data);
                } else {
                    if (typeof func == 'function') func(data);
                }
            })
            return false;
        })
    }
    
    backendIO(values, func) {
        var component = this;
        // Values can be an object or a serialized string
        if (typeof values == 'string') {
            // It is a serialized string

            // Inject class field
            values += '&componentclass='+encodeURIComponent(this.dom_node.data('componentclass'));
            // Inject properties    
            values += '&componentproperties='+encodeURIComponent(this.dom_node.data('componentproperties'));
            // Inject ID
            values += '&componentid='+encodeURIComponent(this.dom_node.prop('id'));
        } else {
            // It is an array/object

            // Inject class field
            values['componentclass'] = this.dom_node.data('componentclass');
            // Inject properties
            values['componentproperties'] = this.dom_node.data('componentproperties');
            // Inject ID
            values['componentid'] = this.dom_node.prop('id');
        }

        // Post
        $.post(this.dom_node.data('io_url'), values, function(data) {
            if (data.destroy) component.dom_node.remove();
            if (data.script) eval(data.script);
            if (data.redirect) {
                if (data.target) 
                    window.open(data.redirect, data.target);
                else 
                    location.href = data.redirect;
            }
            if (data.properties) component.dom_node.data('componentproperties', data.properties);
            if (data.data) {
                $.each(data.data, function(i, v) {
                    component.dom_node.data(i,v);
                });
            }
            if (data.trigger) component.dom_node.trigger(data.trigger, data.parameters);
            if (data.redraw) component.redraw();
            if (typeof func == 'function') func(data);
        }, 'json');
    }
    
    static timed_IO_stack = [];
    static IO_timer = null;

    timedIO(values, callback, polltime, precision) {
        var object = {};
        // Prepare communication object
        object.values = values;
        object.callback = callback;
        object.polltime = polltime;
        object.precision = precision;
        object.component = this;
        object.componentclass = this.dom_node.data('componentclass');
        object.componentproperties = this.dom_node.data('componentproperties');
        object.componentid = this.dom_node.prop('id');
        object.timeleft = polltime;


        // Add or replace to queue
        var inserted = false;
        $.each(Platform.Component.timed_IO_stack, function(id, element) {
            if (element.component == component) {
                Platform.Component.timed_IO_stack[id] = object;
                inserted = true;
                return false;
            }
            return true;
        });
        if (! inserted) Platform.Component.timed_IO_stack.push(object);

        if (! communication_timer) Platform.Component.IO_timer = setTimeout(Platform.Component.executeTimedIO, 1000);
    }

    removeTimedIO() {
        var component = this;
        $.each(Platform.Component.timed_IO_stack, function(id, element) {
            if (element.component == component) {
                Platform.Component.timed_IO_stack.splice(id,1);
                return false;
            }
            return true;
        });
        if (! Platform.Component.timed_IO_stack.length) {
            clearTimeout(Platform.Component.IO_timer);
            Platform.Component.IO_timer = null;
        }
    }

    static executeTimedIO() {
        // Decrease everything and find if something needs to run now
        var run_now = false;
        $.each(Platform.Component.timed_IO_stack, function(id, element) {
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
            $.each(Platform.Component.timed_IO_stack, function(id, element) {
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
                $.each(data, function(id, return_value) {
                    if (callbacks[id]) callbacks[id](return_value);
                })
                communication_timer = setTimeout(Platform.Component.IO_timer, 1000);
            }, 'json');
            // Rearm
        } else {
            if (Platform.Component.timed_IO_stack.length) communication_timer = setTimeout(Platform.Component.IO_timer, 1000);

        }
    }

}

Platform.addCustomFunctionLast(Platform.Component.addComponentClasses);

$.fn.platformComponent = function() {
    return this.data('platform_component');
}