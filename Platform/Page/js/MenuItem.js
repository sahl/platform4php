Platform.addCustomFunction(function(item) {
    /**
     * Update a menuitem
     * @param object data May contain any of the following:
     *                    html   text           HTML encoded text
     *                    bool   iconvisible    true to show, false to hide
     *                    string icon           a Font Awesome icon name, eg. 'fa-edit'
     */
    $('.platform_menuitem', item).on('updatemenuitem', function(event, data) {
        if (typeof data != 'object')    return;
        
        var menuitem = $(this);
        if (data.text !== undefined) {
            var text = data.text;
            if (menuitem.find('span.fa').length > 0 || menuitem.find('img').length > 0)
                text = '&nbsp;'+text;
            menuitem.find('span').html(text);
        }
        if (data.iconvisible !== undefined)
            menuitem.find('span.fa').toggle(data.iconvisible);
        if (data.icon !== undefined)
            menuitem.find('span.fa').attr('class', 'fa '+data.icon);
    });
    $('.platform_menuitem',item).click(function(e) {
        if (Platform.PopupMenu) Platform.PopupMenu.hideAll();
        // Handle special urls
        var url = new String($(this).attr('href'));
        if (url.substr(0,6) == '#POST=') {
            var formtopost = url.substr(6);
            $('#'+formtopost).submit();
            return false;
        }
        if (url.substr(0,9) == '#TRIGGER=') {
            var eventname = url.substr(9);
            var elements = eventname.split('@');
            if (elements.length > 1) {
                $(elements[1]).trigger(elements[0]);
            } else {
                $(this).trigger(eventname);
            }
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
