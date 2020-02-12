$.fn.greyOut = function(enable) {
    
    function cover(element_to_cover, cover_element) {
        cover_element.width(element_to_cover.width());
        cover_element.height(element_to_cover.height());
    }
    
    this.each(function() {
        if (enable) {
            if ($(this).hasClass('platform_greyouted')) return true;
            var greyelement = $('<div class="platform_greyout_element"></div>');
            $(this).prepend(greyelement);
            cover($(this), greyelement);
            $(this).addClass('platform_greyouted');
        } else {
            $(this).find('.platform_greyout_element').remove();
            $(this).removeClass('platform_greyouted');
        }
    })
    return this;
}