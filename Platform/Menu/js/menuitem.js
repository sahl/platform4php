addCustomPlatformFunction(function(item) {
     $('.platform_menuitem',item).click(function(e) {
        // Handle special urls
        var url = new String($(this).attr('href'));
        if (url.substr(0,6) == '!POST=') {
            var formtopost = url.substr(6);
            $('#'+formtopost).submit();
            return false;
        }
        return true;
     });
 });
