var custom_platform_functions = [];


$(function() {
    $('body').applyPlatformFunctions();
});

$.fn.applyPlatformFunctions = function() {
    var object = $(this);
    custom_platform_functions.forEach(function(item) {
        item(object);
    })
}

function addCustomPlatformFunction(fn) {
    custom_platform_functions.push(fn);
}