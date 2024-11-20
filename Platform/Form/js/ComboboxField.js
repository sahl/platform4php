Platform.Form.ComboboxField = class extends Platform.Form.Field {
    
    initialize() {
        var component = this;
        var text_field = this.dom_node.find('input[type="text"]');
        
        // Destroy an already present autocomplete
        text_field.removeData('uiAutocomplete');
        
        // Reapply autocomplete
        if (component.data('use_callback')) {
            text_field.autocomplete({
                source: function(request, callback) {
                    var parameters = {event: 'autocomplete', term: request.term};
                    component.backendIO(parameters, function(data) {
                        callback(data);
                    });
                }
                ,
                minLength: 2,
                open: function(event, ui) {
                    // As the containing form can have moved into a dialog since initialization we check for zindex.
                    var zindex = Platform.Form.ComboboxField.zIndex(text_field);
                    $(text_field).autocomplete('widget').css('z-index', zindex+1);
                }
            });
        } else {
            console.log(component.data('autocomplete_options'));
            text_field.autocomplete({
                source: component.data('autocomplete_options'),
                minLength: 2,
                open: function(event, ui) {
                    // As the containing form can have moved into a dialog since initialization we check for zindex.
                    var zindex = Platform.Form.ComboboxField.zIndex(text_field);
                    $(text_field).autocomplete('widget').css('z-index', zindex+1);
                }
            })
        }

        return true;
    }
    
    static zIndex(element) {
        var zindex = parseInt(element.css('z-index'));
        if (isNaN(zindex)) {
            if (element.parent().length && ! element.is('body')) return Platform.Form.ComboboxField.zIndex(element.parent());
            return 0;
        }
        return zindex;
    }
}

Platform.Component.bindClass('platform_component_form_comboboxfield', Platform.Form.ComboboxField);
