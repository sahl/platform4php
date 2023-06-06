console.log('Load currency');
Platform.Form.CurrencyField = class extends Platform.Form.Field {
    
    initialize() {
        console.log('Initialize currency');
        var component = this;
        $('.currency_currency,.currency_foreignvalue', component.dom_node).change(function() {
            component.backendIO({
                event: 'currency_lookup',
                foreignvalue: component.dom_node.find('.currency_foreignvalue').val(),
                currency: component.dom_node.find('.currency_currency').val()
            }, function(data) {
                if (data.status == 1) component.dom_node.find('.currency_localvalue').val(data.localvalue);
            })
        })
        $('.currency_currency', component.dom_node).change(function() {
            if ($(this).val() == '') $(this).parent().find('.currency_foreignvalue').val('');
        })
    }
    
    clear() {
        this.dom_node.find('.currency_localvalue').val('');
        this.dom_node.find('.currency_currency').val('');
        this.dom_node.find('.currency_foreignvalue').val('');
    }

    getValue() {
        return {
            localvalue: this.dom_node.find('.currency_localvalue').val(),
            currency: this.dom_node.find('.currency_currency').val(),
            foreignvalue: this.dom_node.find('.currency_foreignvalue').val()
        }
    }

    setValue(value) {
        this.dom_node.find('.currency_localvalue').val(value.localvalue);
        this.dom_node.find('.currency_currency').val(value.currency);
        this.dom_node.find('.currency_foreignvalue').val(value.foreignvalue);
    }
    
}

Platform.Component.bindClass('platform_component_currency_field', Platform.Form.CurrencyField);