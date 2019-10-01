addCustomPlatformFunction(function(item) {
    $('.platform_content_box', item).each(function() {
        var element = $(this);
        $.post($(this).data('source'), $(this).data('parameters'), function(data) {
            element.html(data);
            element.applyPlatformFunctions();
            if (element.data('prepare_function')) eval(element.data('prepare_function'))(element);
            element.show(500);
        }, 'html');
    });
});