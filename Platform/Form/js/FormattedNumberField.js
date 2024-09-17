Platform.Form.FormattedNumberField = class extends Platform.Form.Field {
    
    getValue() {
        return Platform.NumberFormat.getUnformattedNumber(this.dom_node.find('input').val());
    }

    setValue(value) {
        this.dom_node.find('input').val(Platform.NumberFormat.getFormattedNumber(value, Math.max(Platform.NumberFormat.getNumberOfDecimals(value), this.dom_node.data('minimum_decimals')), true));
    }
    
    validate() {
        if (! Platform.NumberFormat.isValid(this.dom_node.find('input').val())) {
            this.setError(Platform.Translation.forUser('Invalid number'));
            return false;
        }
        return true;
    }
    
}

Platform.Component.bindClass('platform_component_formatted_number_field', Platform.Form.FormattedNumberField);