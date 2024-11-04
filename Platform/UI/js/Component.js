Platform.Component = class {
    
    handler_classes = [];
    
    dom_node = null;
    
    static dom_classes = [];
    
    static class_library = [];
    
    contained_dialogs = [];
    
    /**
     * Initialize components on the given selector
     * @param {jQuery} selector
     */
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
    
    handleReturnData(data) {
        var component = this;
        if (data.destroy) component.destroy();
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
    }
    
    initialize() {
        
    }
    
    initializeLast() {
        
    }
    
    /**
     * Initialize this component
     */
    componentInitialize() {
        // Gather all dialogs within the component
        this.gatherDialogs();
        // Register backend events
        this.registerBackendEvents();
        // Register backend forms
        this.registerBackendForms();
    }
    
    constructor(dom_node) {
        this.dom_node = dom_node;
        dom_node.data('platform_component', this);
        dom_node.addClass('platform_applied');
    }
    
    /**
     * Register a javascript class to handle a given DOM class name
     * @param {string} dom_class E.g. "SearchComplex"
     * @param {class} javascript_class A class that extends Platform.Component
     */
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
    
    /**
     * Build a new component of the given class
     * @param {string} component_class The Component PHP class
     * @param {object} properties The component properties
     * @param {string} id The wanted ID
     * @param {function} callback A function which will be called with the element
     * @returns {jQuery}
     */
    static build(component_class, properties, id, callback) {
        $.post($('body').data('platform_component_io_url'), {event: 'build', componentclass: component_class, componentproperties: JSON.stringify(properties), componentid: id ? id : ''}, function(data) {
            // Create a div for content
            var container = $('<div></div>');
            // Now attach the received data to this div
            container.append(data);
            // Apply platform to the div
            container.applyPlatform(function() {
                if (typeof callback == 'function') callback(container.children('div'));
            });
        }, 'json');
    }
    
    /**
     * Destroy this component, also removing it from the dom
     */
    destroy() {
        // Destroy any contained components
        $.each(this.getChildren(), function(i, component) {
            component.destroy();
        });
        // Destroy all dialogs within this component
        this.contained_dialogs.forEach(function(value) {
            $('#'+value).dialog('destroy');
        });
        // Remove the dom node
        this.dom_node.remove();        
    }
    
    /**
     * Convenience for making a quick component where only the initialize function is relevant
     * @param {string} dom_class dom_class for element to bind to
     * @param {function} initialize_function The initialize function
     */
    static quickComponent(dom_class, initialize_function) {
        var quick_component = class extends Platform.Component {
            initialize() {
                initialize_function(this);
            }
        }
        Platform.Component.bindClass(dom_class, quick_component);
    }
    
    /**
     * Get the javascript class which handles a specific DOM class
     * @param {string} dom_class DOM class name
     * @returns {object} The associated class or null if no such class
     */
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
    
    /**
     * Convenience for handling data in the DOM
     * @param {string} parameter
     * @param {mixed} option
     */
    data(parameter, option) {
        if (! option) return this.dom_node.data(parameter);
        else this.dom_node.data(parameter, option);
    }
    
    /**
     * Convenience for placing an event handler in the DOM
     * @param {string} event Event name
     * @param {function} callback
     */
    on(event, callback) {
        this.dom_node.on(event, callback);
    }
    
    /**
     * Convenience for removing an event handler from the DOM
     * @param {string} event Event name
     */
    off(event) {
        this.dom_node.off(event);
    }
    
    /**
     * Convenience for triggering an event on the DOM
     * @param {string} event Event to trigger
     * @param {object} payload Payload to pass
     */
    trigger(event, payload) {
        this.dom_node.trigger(event, payload);
    }
    
    /**
     * Convenience for finding a child in the DOM and returning the associated 
     * Platform.Component
     * @param {string} dom_selector Jquery selector to use
     * @returns {object} The associated Platform.Component
     */
    find(dom_selector) {
        return this.dom_node.find(dom_selector).platformComponent();
    }
    
    /**
     * Gather all dialogs within this component, so we can destroy them later as
     * this component is destroyed. This is because dialogs are moved outside the
     * component when initialized.
     */
    gatherDialogs() {
        var component = this;
        $('.platform_base_dialog', this.dom_node).each(function() {
            component.contained_dialogs.push($(this).prop('id'));
            return true;
        })
    }

    /**
     * Redraw this component
     */
    redraw() {
        var component = this;
        if (this.dom_node.is('.platform_container_component')) {
            // Redraw all subcomponents
            $.each(this.getChildren(), function(i, component) {
                component.redraw();
            });
        } else {
            var componentproperties = this.dom_node.data('componentproperties');
            
            // Destroy all dialogs within this component
            this.contained_dialogs.forEach(function(value) {
                $('#'+value).dialog('destroy');
            });

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
    
    /**
     * Register all backend events passed in the data
     */
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

    /**
     * Register all backend forms passed in the data
     */
    registerBackendForms() {
        var component = this;
        // Pass selected forms to backend
        if (this.dom_node.data('registered_form_ids')) {
            $.each(this.dom_node.data('registered_form_ids').split(','), function(index, value) {
                component.addIOForm($('#'+value));
            })
        }
    }
    
    /**
     * Add a form to this component. Such a form will be send to the backend handleIO function
     * when it is submitted
     * @param {jquery} form jquery selector or string
     * @param {function} func Function to call if the backend passes status=true
     * @param {function} failfunc Function to call if the backend passes status=false
     */
    addIOForm(form, func, failfunc) {
        var component = this;
        // Ensure this is Jquery
        form = $(form);
        form.off('submit.ioform').on('submit.ioform', function() {
            component.backendIO($(this).serialize(), function(data) {
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

    /**
     * Send a request to the back handleIO function
     * @param {object} values A serialized string or an object with the values to pass
     * @param {function} func Function to call with the return data
     */
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
            component.handleReturnData(data);
            if (typeof func === 'function') func(data);
        }, 'json');
    }
    
    static timed_IO_stack = [];
    static IO_timer = null;

    /**
     * Register a repeated callback, which should be called at a given interval.
     * @param {object} values The values to pass to the backend
     * @param {function} callback A callback to call when a result is received
     * @param {int} polltime The interval in seconds
     * @param {int} precision The number of seconds we are allowed to deviate from the interval if we can reduce the number of calls
     */
    timedIO(values, callback, polltime, precision) {
        var component = this.dom_node;
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
        
        if (! Platform.Component.IO_timer) Platform.Component.IO_timer = setTimeout(Platform.Component.executeTimedIO, 1000);
    }

    /**
     * Remove all timed IO associated with this component
     */
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

    /**
     * Runs the heartbeat of timed IO
     */
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
            var components = [];
            var url = null;
            $.each(Platform.Component.timed_IO_stack, function(id, element) {
                if (element.timeleft - element.precision <= 0) {
                    var payload = {};
                    if (! url) url = element.component.data('io_url');
                    payload.componentclass = element.componentclass;
                    payload.componentproperties = JSON.stringify(element.componentproperties);
                    payload.componentid = element.componentid;
                    payload.values = (typeof element.values === 'function') ? element.values() : element.values;
                    run_payload.push(payload);
                    callbacks.push(element.callback);
                    components.push(element.component);
                    // Reset element
                    element.timeleft += element.polltime;
                }
            });
            // Call it
            var final_payload = {event: '__timedio', payloads: run_payload};
            $.post(url, final_payload, function(data) {
                $.each(data, function(id, return_value) {
                    components[id].handleReturnData(return_value);
                    if (callbacks[id]) callbacks[id](return_value);
                })
                Platform.Component.IO_timer = setTimeout(Platform.Component.executeTimedIO, 1000);
            }, 'json');
            // Rearm
        } else {
            if (Platform.Component.timed_IO_stack.length) Platform.Component.IO_timer = setTimeout(Platform.Component.executeTimedIO, 1000);

        }
    }
    
    /**
     * Get a property from this Component
     * @param {string} property Property to read
     * @returns The property value
     */
    getProperty(property) {
        var properties = this.dom_node.data('componentproperties');
        return properties[property];
    }
    
    /**
     * Set a property of this Component
     * @param {string} property Property to set
     * @param value Value to assign
     */
    setProperty(property, value) {
        var properties = this.dom_node.data('componentproperties');
        properties[property] = value;
        this.dom_node.data('componentproperties', properties);
    }

    /**
     * Get a list of the child components for the component
     * @param {bool} include_grandchildren
     * @return {array} Array of Platform.Component
     */
    getChildren(include_grandchildren) {
        var children = [];
        var _this = this;
        this.dom_node.find('.platform_applied').each(function() {
            if (!include_grandchildren && !$(this).parent().closest('.platform_applied').is(_this.dom_node))
                return;
            
            children.push($(this).platformComponent());
        })
        return children;
    }
    
    /**
     * Get the parent component, if any
     * @return {object}
     */
    getParent() {
        var parent = this.dom_node.parent().closest('[data-componentclass]');
        if (parent.length == 0)    return null;
        parent = parent.platformComponent();
        if (!(parent instanceof Platform.Component))   return null;
        return parent;
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