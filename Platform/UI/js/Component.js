Platform.Component = class {
    
    handler_classes = [];
    
    dom_node = null;
    
    static dom_classes = [];
    
    static class_library = [];
    
    contained_dialogs = [];
    
    static addComponentClasses(selector) {
        var found_components = [];
        $.each(Platform.Component.class_library, function(key, library_element) {
            selector.find('.'+library_element.dom_class).each(function() {
                var component = new library_element.javascript_class($(this));
                component.componentInitialize();
                found_components.push(component);
            });
            if (selector.is('.'+library_element.dom_class)) {
                var component = new library_element.javascript_class(selector);
                component.componentInitialize();
                found_components.push(component);
            }
        })
        $.each(found_components, function(key, component) {
            component.initialize();
        })
        
        $.each(found_components, function(key, component) {
            component.initializeLast();
            component.dom_node.triggerHandler('component_ready');
        })
    }
    
    initialize() {
        
    }
    
    initializeLast() {
        
    }
    
    componentInitialize() {
        this.gatherDialogs();
        this.registerBackendEvents();
        this.registerBackendForms();
    }
    
    constructor(dom_node) {
        this.dom_node = dom_node;
        dom_node.data('platform_component', this);
    }
    
    static bindClass(dom_class, javascript_class) {
        // Ensure we only add everything once.
        if (Platform.Component.dom_classes.includes(dom_class)) return;

        var library_element = {
            dom_class: dom_class,
            javascript_class: javascript_class,
        }
        
        Platform.Component.dom_classes.push(dom_class);
        Platform.Component.class_library.push(library_element);
    }
    
    static getClassBinding(dom_class) {
        var result = null;
        $.each(Platform.Component.class_library, function(key, library_element) {
            if (dom_class == library_element.dom_class) {
                result = library_element.javascript_class;
                return false;
            }
        })
        return result;
    }
    
    
    
    gatherDialogs() {
        var component = this;
        $('.platform_base_dialog', this.dom_node).each(function() {
            component.contained_dialogs.push($(this).prop('id'));
            return true;
        })
    }
    
    redraw() {
        if (this.dom_node.is('.platform_container_component')) {
            // Redraw all subcomponents
            this.dom_node.find('.platform_component').each(function() {
                if ($(this).parent().closest('.platform_component')[0] == this.dom_node[0]) {
                    // Redraw subcomponent.
                    $(this).platformComponent().redraw();
                }
            })
        } else {
            var componentproperties = this.dom_node.data('componentproperties');
            
            // Destroy all dialogs within this component
            this.contained_dialogs.forEach(function(value) {
                $('#'+value).dialog('destroy');
            })

            var component = this;
            
            // Destroy handlers
            component.dom_node.off();
            
            this.backendIO({event: 'redraw', componentclass: this.dom_node.data('componentclass'), componentproperties: componentproperties, componentid: this.dom_node.prop('id')}, function(data) {
                component.dom_node.html(data);
                component.dom_node.data('platform_component', null); // Remove existing platform reference
                Platform.apply(component.dom_node);
            });
            
        }
    }
    
    registerBackendEvents() {
        var component = this;
        // Pass custom events to backend
        if (this.dom_node.data('registered_events')) {
            $.each(this.dom_node.data('registered_events').split(','), function(index, value) {
                component.dom_node.off(value);
                component.dom_node.on(value, function(event, parameter1, parameter2, parameter3) {
                    if (parameter1 == '__data_request_event') {
                        // This is a datatable event, which needs to be handled another way
                        parameter2['event'] = value;
                        component.backendIO(parameter2, function(data) {
                            parameter3(data);
                        }); 
                    } else {
                        component.backendIO({event: value, parameter1: parameter1, parameter2: parameter2, parameter3: parameter3 }); 
                    }
                    return false;
                });
            })
        }
    }
    
    registerBackendForms() {
        var component = this;
        // Pass selected forms to backend
        if (this.dom_node.data('registered_form_ids')) {
            $.each(this.dom_node.data('registered_form_ids').split(','), function(index, value) {
                component.addIOForm($('#'+value, component.dom_node));
            })
        }
    }
    
    addIOForm(form, func, failfunc) {
        var component = this;
        form.off('submit.ioform').on('submit.ioform', function() {
            component.backendIO(form.serialize(), function(data) {
                if (! data.status) {
                    form.closest('.platform_component_form').platformComponent().attachErrors(data.form_errors);
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
            values += '&componentproperties='+encodeURIComponent(JSON.stringify(this.dom_node.data('componentproperties')));
            // Inject ID
            values += '&componentid='+encodeURIComponent(this.dom_node.prop('id'));
        } else {
            // It is an array/object

            // Inject class field
            values['componentclass'] = this.dom_node.data('componentclass');
            // Inject properties
            values['componentproperties'] = JSON.stringify(this.dom_node.data('componentproperties'));
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
        var component = this.dom_node;
        var object = {};
        // Prepare communication object
        object.values = values;
        object.callback = callback;
        object.polltime = polltime;
        object.precision = precision;
        object.component = this.dom_node;
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
        
        if (! Platform.Component.IO_timer) Platform.Component.IO_timer = setTimeout(Platform.Component.executeTimedIO, 1000);
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
                    payload.values = (typeof element.values == 'function') ? element.values() : element.values;
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
                Platform.Component.IO_timer = setTimeout(Platform.Component.executeTimedIO, 1000);
            }, 'json');
            // Rearm
        } else {
            if (Platform.Component.timed_IO_stack.length) Platform.Component.IO_timer = setTimeout(Platform.Component.executeTimedIO, 1000);

        }
    }

}

Platform.addCustomFunction(Platform.Component.addComponentClasses);

Platform.Component.bindClass('platform_component', Platform.Component);

$.fn.platformComponent = function() {
    var component = this.data('platform_component');
    if (component) return component;
    if (this.parent().length) return this.parent().platformComponent();
    return null;
}