addPlatformComponentHandlerFunction('popupmenu', function(item) {
    item.on('appear', function(event, clickevent, parameters) {
        if (parameters == null) parameters = {};
        var element = parameters.appear_on ? parameters.appear_on : clickevent.target;
        if (parameters.info) item.data('platform_info', parameters.info);
        switch (item.data('location')) {
            case 10:
                item.css('left', $(element).offset().left);
                item.css('top', $(element).offset().top-item.height());
            break;
            case 20:
                item.css('left', $(element).offset().left);
                item.css('top', $(element).offset().top+$(element).height());
            break;
            case 30:
                item.css('left', $(element).offset().left-item.width());
                item.css('top', $(element).offset().top);
            break;
            case 40:
                item.css('left', $(element).offset().left+$(element).width());
                item.css('top', $(element).offset().top);
            break;
            break;
            default:
                var left = $(element).offset().left+$(element).width()/2;
                var top = $(element).offset().top+$(element).height()/2;
                if (clickevent) {
                    left = clickevent.pageX+5;
                    top = clickevent.pageY+5;
                }
                item.css('left', left);
                item.css('top', top);
            break;
        }
        $(window).on('click', function() {
            hidePopupMenu();
        });
        item.show();
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
            item.trigger('appear', [$(this), event]);
        })
    }
})