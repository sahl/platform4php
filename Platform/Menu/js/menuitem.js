addCustomPlatformFunction(function(item) {
     $('.platform_menuitem',item).click(function(e) {
        // Handle special urls
        var url = new String($(this).attr('href'));
        if (url.substr(0,6) == '!POST=') {
            var formtopost = url.substr(6);
            $('#'+formtopost).submit();
            return false;
        }
        if (url.substr(0,12) == '!DIALOGOPEN=') {
            var dialog_to_open = url.substr(12);
            $('#'+dialog_to_open).dialog('open');
        }
        return true;
     });
 });
