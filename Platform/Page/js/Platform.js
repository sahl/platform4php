var Platform = {
    
    custom_functions: [],
    
    custom_functions_last: [],
    
    functions_registered: [],
    
    onetime_functions: [],
    
    scripts_loaded: [],
    
    did_onetime_run: false,
    
    apply(selector, callback_when_applied) {
        selector = $(selector);
        // Check for scripts to postload
        var script_containers = $('.platform_post_load_javascript', selector);
        var script_count = script_containers.length;
        // If there are no scripts, we can run the functions now
        if (script_count == 0) {
            Platform.runCustomFunctions(selector);
            return;
        }
        // Gather them
        var scripts_to_load = [];
        script_containers.each(function() {
            var src = $(this).data('src');
            $(this).remove();
            scripts_to_load.push(src);
        })
        
        function loadNextScript(callback_when_applied) {
            var src = scripts_to_load.shift();
            if (Platform.scripts_loaded.includes(src)) {
                if (scripts_to_load.length) {
                    loadNextScript(callback_when_applied);
                } else {
                    Platform.runCustomFunctions(selector,callback_when_applied);
                }
            } else {
                $.get(src).done(function() {
                    Platform.registerScript(src);
                    if (scripts_to_load.length) loadNextScript(callback_when_applied);
                    else Platform.runCustomFunctions(selector, callback_when_applied);
                }).fail(function(jqxhr, settings, exception) {
                    console.log('Error loading '+src);
                    console.log(jqxhr);
                    console.log(settings);
                    console.log(exception);
                    if (scripts_to_load.length) loadNextScript(callback_when_applied);
                    else Platform.runCustomFunctions(selector, callback_when_applied);
                });
            }
        }
        loadNextScript(callback_when_applied);
    },
    
    /**
     * Gather all scripts on the page
     * @returns {undefined}
     */
    gatherAllScriptsFromPage() {
        $('script').each(function() {
            var src = $(this).attr('src');
            Platform.registerScript(src);
        })
    },
    
    /**
     * Register a javascript as loaded
     * @param {string} src Javascript 
     * @returns {undefined}
     */
    registerScript(src) {
        Platform.scripts_loaded.push(src);
    },
    
    runCustomFunctions(selector, callback_when_applied) {
        Platform.custom_functions.forEach(function(fn) {
            fn(selector);
        });
        Platform.custom_functions_last.forEach(function(fn) {
            fn(selector);
        });
        if (! Platform.did_onetime_run) {
            Platform.did_onetime_run = true;
            Platform.onetime_functions.forEach(function(fn) {
                fn();
            });
        }
        if (typeof callback_when_applied == 'function') callback_when_applied();
    },
    
    addCustomFunction(fn) {
        var fn_as_string = fn.toString();
        if (Platform.functions_registered.includes(fn_as_string)) return;
        Platform.functions_registered.push(fn_as_string);
        Platform.custom_functions.push(fn);
    },
    
    addCustomFunctionLast(fn) {
        var fn_as_string = fn.toString();
        if (Platform.functions_registered.includes(fn_as_string)) return;
        Platform.functions_registered.push(fn_as_string);
        Platform.custom_functions_last.push(fn);
    },
    
    addCSSPostLoader() {
        Platform.addCustomFunctionLast(function(selector) {
            $('.platform_css_postload', selector).each(function() {
                var css_file = $(this).html();
                var href = $(this).attr('href');
                $(this).remove();
                // See if it is already in header
                var present = false;
                $('link').each(function() {
                    if (href == css_file) {
                        present = true;
                        return false;
                    }
                })
                if (! present) {
                    $('head').append('<link rel="stylesheet" href="'+css_file+'" type="text/css">');
                }
            })
        })
    },
    
    escapeSelector(selector) {
        return selector.replace( /(:|\.|\[|\]|,)/g, "\\$1" ); 
    },
    
    ready(func) {
        Platform.onetime_functions.push(func);
    }
    
};

$(function() {
    Platform.gatherAllScriptsFromPage();
    Platform.addCSSPostLoader();
    Platform.apply('body');
});

$.fn.applyPlatform = function(callback_when_applied) {
    Platform.apply(this, callback_when_applied);
    return this;
};

$.fn.closestChildren = function(selector) {
    if ($(this).is(selector)) return $(this);
    var result = $();
    $(this).children().each(function() {
        result = result.add($(this).closestChildren(selector));
    });
    return result;
}