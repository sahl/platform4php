Platform.PopupMenu = class extends Platform.Component {
    
    initialize() {
        var component = this;
        if (this.dom_node.data('attach_to')) {
            var dom_node = $(this.dom_node.data('attach_to'));
            dom_node.click(function (event) {
                component.show(event, { 'appear_on': dom_node });
            })
        }
        this.dom_node.click(Platform.PopupMenu.hideAll);
    }
    
    show(clickevent, parameters) {
        var component = this;
        var dom_element = this.dom_node;
        if (! parameters) parameters = {};
        
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
        // Prevent menu from exiting the screen
        if (left-$(window).scrollLeft() + dom_element.outerWidth() > $(window).width()-5) left = $(window).scrollLeft()+$(window).width() - dom_element.outerWidth()-5;
        if (top-$(window).scrollTop() + dom_element.outerHeight() > $(window).height()-5) top = $(window).scrollTop()+$(window).height() - dom_element.outerHeight()-5;
        if (left-$(window).scrollLeft() < 5) left = $(window).scrollLeft() + 5;
        if (top-$(window).scrollTop() < 5) top = $(window).scrollTop() + 5;
        
        Platform.PopupMenu.hideAll();
        
        // Adjust for any parent absolute container
        dom_element.css('left', 0);
        dom_element.css('top', 0);
        dom_element.show();
        left -= dom_element.offset().left;
        top -= dom_element.offset().top;
        
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