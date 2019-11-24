addCustomPlatformFunction(function(item) {
    $('.platform_content_box', item).contentbox('refresh');
});


$.fn.contentbox = function(action, param1, param2) {
    this.each(function() {
        if (action == 'refresh') {
            var element = $(this);
            $.post($(this).data('source'), $(this).data('parameters'), function(data) {
                if (element.is(':visible')) {
                    element.hide(element.data('reveal'), {}, 300, function() {
                        element.html(data);
                        element.children().applyPlatformFunctions();
                        if (element.data('prepare_function')) eval(element.data('prepare_function'))(element);
                        element.show(element.data('reveal'), {}, 300);
                        if (typeof(param1) == 'function') param1(element);
                    })
                } else {
                    element.html(data);
                    element.children().applyPlatformFunctions();
                    element.trigger('contentbox_updated');
                    element.show(element.data('reveal'), {}, 600);
                    if (typeof(param1) == 'function') param1(element);
                }
            }, 'html');
        } else if (action == 'source') {
            $(this).data('source', param1).contentbox('refresh', param2);
        }
    })
}