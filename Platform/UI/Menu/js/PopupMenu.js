addPlatformComponentHandlerFunction('popupmenu', function(item) {
    item.on('appear', function(event, clickevent, parameters) {
        if (parameters == null) parameters = {};
        var element = parameters.appear_on ? parameters.appear_on : clickevent.target;
        if (parameters.info) item.data('platform_info', parameters.info);
        switch (item.data('location')) {
            case 10:
                left = $(element).offset().left;
                top = $(element).offset().top-item.height();
            break;
            case 20:
                left = $(element).offset().left;
                top = $(element).offset().top+$(element).height();
            break;
            case 30:
                left = $(element).offset().left-item.width();
                top = $(element).offset().top;
            break;
            case 40:
                left = $(element).offset().left+$(element).width();
                top = $(element).offset().top;
            break;
            default:
                var left = $(element).position().left+$(element).width()/2;
                var top = $(element).position().top+$(element).height()/2;
                if (clickevent) {
                    left = clickevent.pageX+5;
                    top = clickevent.pageY+5;
                }
            break;
        }
        // Adjust for any parent absolute container
        item.css('left', 0);
        item.css('top', 0);
        item.show();
        left -= item.offset().left;
        top -= item.offset().top;
        
        item.css('left', left);
        item.css('top', top);
        $(window).on('click', function() {
            hidePopupMenu();
        });
        clickevent.stopPropagation();
    });
    
    // Redirect click in div to link
    item.find('div').click(function(event) {
        // Only click through if click originated here
        if (event.target != this) return;
        $(this).find('a').click();
    })
    
    item.find('a').click(hidePopupMenu);
    
    function hidePopupMenu() {
        if (! item.is(':visible')) return;
        item.hide();
        $(window).off('click', hidePopupMenu);
    }
    
    if (item.data('attach_to')) {
        $(item.data('attach_to')).click(function(event) {
            item.trigger('appear', [event]);
        })
    }
})