Platform.addCustomFunction(function(item) {
     $('.platform_menuitem',item).click(function(e) {
        // Handle special urls
        var url = new String($(this).attr('href'));
        if (url.substr(0,6) == '#POST=') {
            var formtopost = url.substr(6);
            $('#'+formtopost).submit();
            return false;
        }
        if (url.substr(0,9) == '#TRIGGER=') {
            var eventname = url.substr(9);
            $(this).trigger(eventname);
            return false;
        }
        if (url.substr(0,12) == '#DIALOGOPEN=') {
            var dialog_to_open = url.substr(12);
            $('#'+dialog_to_open).dialog('open');
            return false;
        }
        return true;
     });
 });
