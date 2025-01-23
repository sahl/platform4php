Platform.Form.DateTimeField = class extends Platform.Form.Field {

    /**
     * Get the value of this field
     * @returns The value
     */
    getValue() {
        var value = super.getValue();
        if (value != '') {
            value = Platform.Time.convertLocalToUTC(value.replace(/T/, ' '));
        }
        return value;
    }    
    
    /**
     * Set the value of this field
     * @param value
     */
    setValue(value) {
        if (value != '') {
            value = Platform.Time.convertUTCToLocal(value).replace(/ /, 'T');
        }
        super.setValue(value);
    }
    
}

Platform.Component.bindClass('platform_component_date_time_field', Platform.Form.DateTimeField);