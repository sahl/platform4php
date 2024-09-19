Platform.Form.FormattedNumberField = class extends Platform.Form.Field {
    
    getValue() {
        return Platform.NumberFormat.getUnformattedNumber(this.dom_node.find('input').val());
    }

    setValue(value) {
        this.dom_node.find('input').val(Platform.NumberFormat.getFormattedNumber(value, Math.max(Platform.NumberFormat.getNumberOfDecimals(value), this.dom_node.data('minimum_decimals')), true));
    }
    
    validate() {
        var result = super.validate();
        if (! result) return false;
        
        var value = this.dom_node.find('input').val();
        // Only validate number if filled
        if (value && ! Platform.NumberFormat.isValid(value)) {
            this.setError(Platform.Translation.forUser('Invalid number'));
            return false;
        }
        return true;
    }
    
}

Platform.Component.bindClass('platform_component_formatted_number_field', Platform.Form.FormattedNumberField);