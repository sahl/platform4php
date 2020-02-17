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