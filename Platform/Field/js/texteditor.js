addCustomPlatformFunction(function(item) {
    $('textarea.texteditor', item).each(function() {
        var element = $(this);
        element.summernote({
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol']],
                ['color', ['color']],
                ['control', ['undo', 'redo', 'fullscreen']],
            ],
            height: element.height()+100,
            codeviewFilter: true,
            focus: false,
        });
        return true;
    });
});

