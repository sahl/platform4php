Platform.Form.Texteditor = class extends Platform.Form.Field {
    
    textarea = null
    
    initialize() {
        this.textarea = this.dom_node.find('textarea');
        this.textarea.summernote({
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol']],
                ['color', ['color']],
                ['control', ['undo', 'redo', 'fullscreen']],
            ],
            height: this.dom_node.height()+100,
            codeviewFilter: true,
            focus: false,
        });
    }
    
    clear() {
        this.textarea.val('');
    }

    getValue() {
        return this.textarea.val();
    }

    setValue(value) {
        this.clear();
        this.textarea.val(value);
        this.textarea.summernote('code', value);
    }
    
}

Platform.Component.bindClass('platform_component_texteditor_field', Platform.Form.Texteditor);