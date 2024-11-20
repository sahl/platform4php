Platform.Form.IndexedComboboxField = class extends Platform.Form.ComboboxField {
    
    initialize() {
        var component = this;
        var id_field = this.dom_node.find('input[type="hidden"]');
        var text_field = this.dom_node.find('input[type="text"]');
        // Destroy an already present autocomplete
        text_field.removeData('uiAutocomplete');
        text_field.autocomplete({
            source: function(request, callback) {
                var options = {event: 'autocomplete', term: request.term};
                component.backendIO(options, function(data) {
                    callback(data);
                });
            },
            minLength: 2,
            select: function(event, ui) {
                if (ui.item.real_id) {
                    id_field.val(ui.item.real_id);
                    text_field.data('validated_value', ui.item.value);
                }
            },
            change: function(event, ui) {
                if (text_field.val() != text_field.data('validated_value')) id_field.val('0');
            },
            open: function(event, ui) {
                // As the containing form can have moved into a dialog since initialization we check for zindex.
                var zindex = Platform.Form.ComboboxField.zIndex(text_field);
                $(text_field).autocomplete('widget').css('z-index', zindex+1);
            }
        }).data( "ui-autocomplete" )._renderItem = function(ul, item) {
            ul.addClass('platform_component_combobox_searchresult');
            return $("<div id='list_autocomplete'></div>")
                .data("item.autocomplete", item)
                .append(item.value)
                .appendTo(ul);
        }

        return true;
    }
    
    clear() {
        this.setValue({id: 0, visual: ''});
    }
    
    setValue(value) {
        var id_field = this.dom_node.find('input[type="hidden"]');
        var text_field = this.dom_node.find('input[type="text"]');
        if (value.id === undefined) {
            if (! value) {
                id_field.val('0');
                text_field.val('');
                return;
            }
            text_field.val('...');
            this.backendIO({event: 'resolve', id: value}, function(data) {
                if (data.status) {
                    id_field.val(data.real_id);
                    text_field.val(data.visual);
                    text_field.data('validated_value', data.visual);
                }
            })
        } else {
            id_field.val(value.id);
            text_field.val(value.visual);
            text_field.data('validated_value', value.visual);
        }
    }
    
    getValue() {
        var id_field = this.dom_node.find('input[type="hidden"]');
        var text_field = this.dom_node.find('input[type="text"]');
        return {visual: text_field.val(), id: id_field.val()};
    }
    
    isEmpty() {
        return this.dom_node.find('input[type="text"]').val() == '';
    }
}

Platform.Component.bindClass('platform_component_form_indexed_combobox_field', Platform.Form.IndexedComboboxField);
