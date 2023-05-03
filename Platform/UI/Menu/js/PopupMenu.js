addPlatformComponentHandlerFunction('popupmenu', function(item) {
    item.on('appear', function(event, clickevent, parameters) {
        item.trigger('popup_open');
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
        // Close other popup menus
        platform_PopupMenu_hide();
        // Adjust for any parent absolute container
        item.css('left', 0);
        item.css('top', 0);
        item.show();
        left -= item.offset().left;
        top -= item.offset().top;
        
        // Prevent menu from exiting the screen
        if (left + item.width() > $(window).width()-5) left = $(window).width() - item.width()-5;
        if (top + item.height() > $(window).height()-5) top = $(window).height() - item.height()-5;
        item.css('left', left);
        item.css('top', top);
        clickevent.stopPropagation();
    });
    
    // Redirect click in div to link
    item.find('div').click(function(event) {
        // Only click through if click originated here
        if (event.target != this) return;
        $(this).find('a').click();
    })
    
    item.find('a').click(platform_PopupMenu_hide);
    
    if (item.data('attach_to')) {
        $(item.data('attach_to')).click(function(event) {
            item.trigger('appear', [event]);
        })
    }
})

// Hide all popup menus on generel click or resize
$(window).on('click', platform_PopupMenu_hide);
$(window).on('resize', platform_PopupMenu_hide);
function platform_PopupMenu_hide() {
    $('.platform_component_popupmenu:visible').hide();
}
