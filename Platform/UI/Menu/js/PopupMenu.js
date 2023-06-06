Platform.PopupMenu = class extends Platform.Component {
    
    initialize() {
        if (this.dom_node.data('attach_to')) {
            $(this.dom_node.data('attach_to')).click(function(event) {
                this.show(event);
            })
        }
    }
    
    show(clickevent, parameters) {
        var component = this;
        var dom_element = this.dom_node;
        
        dom_element.trigger('appear');
        var target_element = parameters.appear_on ? parameters.appear_on : clickevent.target;
        if (parameters.info) dom_element.data('platform_info', parameters.info);
        switch (dom_element.data('location')) {
            case 10:
                left = $(target_element).offset().left;
                top = $(target_element).offset().top-item.height();
            break;
            case 20:
                left = $(target_element).offset().left;
                top = $(target_element).offset().top+$(target_element).height();
            break;
            case 30:
                left = $(target_element).offset().left-item.width();
                top = $(target_element).offset().top;
            break;
            case 40:
                left = $(target_element).offset().left+$(target_element).width();
                top = $(target_element).offset().top;
            break;
            default:
                var left = $(target_element).position().left+$(target_element).width()/2;
                var top = $(target_element).position().top+$(target_element).height()/2;
                if (clickevent) {
                    left = clickevent.pageX+5;
                    top = clickevent.pageY+5;
                }
            break;
        }
        Platform.PopupMenu.hideAll();
        // Adjust for any parent absolute container
        dom_element.css('left', 0);
        dom_element.css('top', 0);
        dom_element.show();
        left -= dom_element.offset().left;
        top -= dom_element.offset().top;
        
        // Prevent menu from exiting the screen
        if (left + dom_element.width() > $(window).width()-5) left = $(window).width() - dom_element.width()-5;
        if (top + dom_element.height() > $(window).height()-5) top = $(window).height() - dom_element.height()-5;
        dom_element.css('left', left);
        dom_element.css('top', top);
        clickevent.stopPropagation();
    }
    
    static hideAll() {
        $('.platform_menu_popupmenu:visible').hide().trigger('disappear');
    }
}

// Hide all popup menus on generel click or resize
$(window).on('click', Platform.PopupMenu.hideAll);
$(window).on('resize', Platform.PopupMenu.hideAll);

Platform.Component.bindClass('platform_menu_popupmenu', Platform.PopupMenu);