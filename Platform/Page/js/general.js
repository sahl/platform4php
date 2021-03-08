var custom_platform_functions = [];
var custom_platform_functions_last = [];


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
}

function addCustomPlatformFunction(fn) {
    custom_platform_functions.push(fn);
}

function addCustomPlatformFunctionLast(fn) {
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