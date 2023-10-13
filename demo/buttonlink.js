Platform.addCustomFunction(function(item) {
    $('button', item).click(function() {
        if ($(this).data('destination')) {
            document.location = $(this).data('destination');
            return false;
        }
        return true;
    });
});


