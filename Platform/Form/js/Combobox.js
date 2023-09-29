Platform.Form.ComboboxField = class extends Platform.Form.Field {
    
    initialize() {
        var component = this;
        var element = this.dom_node.find('input[type="text"]');
        // Destroy an already present autocomplete
        element.removeData('uiAutocomplete');
        element.autocomplete({
            source: function(request, callback) {
                var options = {event: 'autocomplete', term: request.term};
                if (element.data('filter')) options.filter = element.attr('data-filter');
                component.backendIO(options, function(data) {
                    callback(data.callback_options);
                })
            },
            minLength: 2,
            select: function(event, ui) {
                if (ui.item.real_id) {
                    element.prev().val(ui.item.real_id);
                    element.data('validated_value', ui.item.value);
                }
            },
            change: function(event, ui) {
                if (element.val() != element.data('validated_value')) $(this).prev().val('0');
            },
            open: function(event, ui) {
                // As the containing form can have moved into a dialog since initialization we check for zindex.
                var zindex = Platform.Form.ComboboxField.zIndex(element);
                $(element).autocomplete('widget').css('z-index', zindex+1);
            }
        });
        return true;
    }
    
    clear() {
        this.setValue({id: 0, visual: ''});
    }
    
    setValue(value) {
        var element = this.dom_node.find('input[type="text"]');
        if (value.id === undefined) {
            element.val('...');
            this.backendIO({event: 'resolve', id: value}, function(data) {
                element.prev().val(value);
                element.val(data.visual);
                element.data('validated_value', data.visual);
            })
        } else {
            element.val(value.visual);
            element.prev().val(value.id);
            element.data('validated_value', value.visual);
        }
    }
    
    getValue() {
        var element = this.dom_node.find('input[type="text"]');
        return {visual: element.val(), id: element.prev().val()};
    }
    
    isEmpty() {
        return this.dom_node.find('input[type="text"]').val() == '';
    }
    
    static zIndex(element) {
        var zindex = parseInt(element.css('z-index'));
        if (isNaN(zindex)) {
            if (element.parent().length) return Platform.Form.ComboboxField.zIndex(element.parent());
            return 0;
        }
        return zindex;
    }
    
}

Platform.Component.bindClass('platform_component_form_comboboxfield', Platform.Form.ComboboxField);
