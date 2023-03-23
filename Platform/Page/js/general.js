var custom_platform_functions = [];
var custom_platform_functions_last = [];
var functions_registered = [];

var platform_scripts_registered = [];

$(function() {
    // Register all scripts on page
    $('script').each(function() {
        var src = $(this).attr('src');
        platform_scripts_registered.push(src);
    });

    $('body').applyPlatformFunctions();
    
});

$.fn.applyPlatformFunctions = function() {
    var object = $(this);
    // Check for scripts to postload
    var script_containers = $('.platform_post_load_javascript');
    var script_count = script_containers.length;
    // If there are no scripts, we can run the functions now
    if (script_count == 0) runCustomPlatformFunctions(object);
    script_containers.each(function() {
        var src = $(this).data('src');
        $(this).remove();
        if (! platform_scripts_registered.includes(src)) {
            platform_scripts_registered.push(src);
            $.getScript(src, function() {
                script_count--;
                // If last script is loaded, we can run functions now.
                if (script_count < 1) runCustomPlatformFunctions(object);
            });
        } else {
            script_count--;
            // If last script is skipped, we can run functions now.
            if (script_count < 1) runCustomPlatformFunctions(object);
        }
    });
    return this;
}

function runCustomPlatformFunctions(object) {
    // All scripts are loaded. Now we can apply functions
    custom_platform_functions.forEach(function(item) {
        item(object);
    });
    custom_platform_functions_last.forEach(function(item) {
        item(object);
    });
}

function addCustomPlatformFunction(fn) {
    var fn_as_string = fn.toString();
    if (functions_registered.includes(fn_as_string)) return;
    functions_registered.push(fn_as_string);
    custom_platform_functions.push(fn);
}

function addCustomPlatformFunctionLast(fn) {
    var fn_as_string = fn.toString();
    if (functions_registered.includes(fn_as_string)) return;
    functions_registered.push(fn_as_string);
    custom_platform_functions_last.push(fn);
}

addCustomPlatformFunctionLast(function(item) {
    $('.platform_css_postload', item).each(function() {
        var css_file = $(this).html();
        // See if it is already in header
        var present = false;
        $('link').each(function() {
            if ($(this).attr('href') == css_file) {
                present = true;
                return false;
            }
        })
        if (! present) {
            $('head').append('<link rel="stylesheet" href="'+css_file+'" type="text/css">');
        }
        $(this).remove();
    })
})