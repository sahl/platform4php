var custom_platform_functions = [];
var custom_platform_functions_last = [];
var functions_registered = [];

$(function() {
    $('body').applyPlatformFunctions();
});

$.fn.applyPlatformFunctions = function() {
    var object = $(this);
    custom_platform_functions.forEach(function(item) {
        item(object);
    })
    custom_platform_functions_last.forEach(function(item) {
        item(object);
    })
    return this;
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