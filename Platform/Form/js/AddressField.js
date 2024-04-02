Platform.Form.AdressField = class extends Platform.Form.Field {
        
    clear() {
        this.dom_node.find('.address_address').val('');
        this.dom_node.find('.address_address2').val('');
        this.dom_node.find('.address_city').val('');
        this.dom_node.find('.address_zip').val('');
        this.dom_node.find('.address_countrycode').val('');
    }

    getValue() {
        return {
            address: this.dom_node.find('.address_address').val(),
            address2: this.dom_node.find('.address_address2').val(),
            city: this.dom_node.find('.address_city').val(),
            zip: this.dom_node.find('.address_zip').val(),
            countrycode: this.dom_node.find('.address_countrycode').val()
        }
    }

    setValue(value) {
        this.dom_node.find('.address_address').val(value.address);
        this.dom_node.find('.address_address2').val(value.address2);
        this.dom_node.find('.address_city').val(value.city);
        this.dom_node.find('.address_zip').val(value.zip);
        this.dom_node.find('.address_countrycode').val(value.countrycode);
    }
    
}

Platform.Component.bindClass('platform_component_address_field', Platform.Form.AdressField);