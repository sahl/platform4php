addCustomPlatformFunction(function(item) {
    $('.platform_content_box', item).contentbox('refresh');
});


$.fn.contentbox = function(action, param1, param2) {
    this.each(function() {
        if (action == 'refresh') {
            var element = $(this);
            $.post($(this).data('source'), $(this).data('parameters'), function(data) {
                if (element.is(':visible')) {
                    element.hide(250, function() {
                        element.html(data);
                        element.applyPlatformFunctions();
                        if (element.data('prepare_function')) eval(element.data('prepare_function'))(element);
                        element.show(250);
                        if (typeof(param1) == 'function') param1(element);
                    })
                } else {
                    element.html(data);
                    element.applyPlatformFunctions();
                    if (element.data('prepare_function')) eval(element.data('prepare_function'))(element);
                    element.show(500);
                    if (typeof(param1) == 'function') param1(element);
                }
            }, 'html');
        } else if (action == 'source') {
            $(this).data('source', param1).contentbox('refresh', param2);
        }
    })
}