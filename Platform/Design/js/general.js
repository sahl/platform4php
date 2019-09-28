var custom_platform_functions = [];


$(function() {
    $('body').applyPlatformFunctions();
});

$.fn.applyPlatformFunctions = function() {
    $('button', this).each(function() {
        var destination = $(this).data('destination');
        if (destination) {
            $(this).click(function() {
                document.location.href = destination;
            });
        }
    });
    var object = this;
    custom_platform_functions.forEach(function(item) {
        item(object);
    })
    
}

function addCustomPlatformFunction(fn) {
    custom_platform_functions.push(fn);
}