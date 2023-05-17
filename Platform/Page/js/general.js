var Platform = {
    
    custom_functions: [],
    
    custom_functions_last: [],
    
    functions_registered: [],
    
    scripts_loaded: [],
    
    apply(selector) {
        selector = $(selector);
        // Check for scripts to postload
        var script_containers = $('.platform_post_load_javascript');
        var script_count = script_containers.length;
        // If there are no scripts, we can run the functions now
        if (script_count == 0) Platform.runCustomFunctions(selector);
        script_containers.each(function() {
            var src = $(this).data('src');
            $(this).remove();
            if (! Platform.scripts_loaded.includes(src)) {
                Platform.registerScript(src);
                $.getScript(src, function() {
                    script_count--;
                    // If last script is loaded, we can run functions now.
                    if (script_count < 1) Platform.runCustomFunctions(selector);
                });
            } else {
                script_count--;
                // If last script is skipped, we can run functions now.
                if (script_count < 1) Platform.runCustomFunctions(selector);
            }
        });
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
    
    runCustomFunctions(selector) {
        Platform.custom_functions.forEach(function(fn) {
            fn(selector);
        });
        Platform.custom_functions_last.forEach(function(fn) {
            fn(selector);
        });
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
    
};

$(function() {
    Platform.gatherAllScriptsFromPage();
    Platform.addCSSPostLoader();
    Platform.apply('body');
});

