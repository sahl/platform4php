Platform.NumberFormat = class {

    /**
     * Check if the passed string is a correctly formatted "formatted number"
     * @param {string} number
     * @returns {bool}
     */
    static isValid(number) {
        var number_string = new String(number);
        var validation_regexp = new RegExp('^(\\d{1,3}\\'+Platform.NumberFormat.getThousandSeparator()+'?)(\\d{3}\\'+Platform.NumberFormat.getThousandSeparator()+'?)*('+Platform.NumberFormat.getDecimalSeparator()+'\\d+)?$', 'g')
        return validation_regexp.test(number_string);
    }
    
    /**
     * Get the decimal separator character
     * @returns {string}
     */
    static getDecimalSeparator() {
        switch (Platform.NumberFormat.getNumberFormat()) {
            case 1:
                return ',';
            case 2:
                return '.';
            default:
                console.error('Unknown number format');
                console.log(Platform.NumberFormat.getNumberFormat());
                return '';
        }
    }
    
    /**
     * Return the current locale to be used for numbers
     * @returns {string}
     */
    static getLocaleString() {
        switch (Platform.NumberFormat.getNumberFormat()) {
            case 1:
                return 'da-DK';
            case 2:
                return 'en-US';
            default:
                console.error('Unknown number format');
                console.log(Platform.NumberFormat.getNumberFormat());
                return '';
        }
    }
    
    /**
     * Get the current number format as indicated from the backend
     * @returns {int}
     */
    static getNumberFormat() {
        return parseInt($('body').data('platform_number_format'));
    }
    
    /**
     * Get the number of decimals in an unformatted value
     * @param {float} value
     * @returns {int} Number of decimals
     */
    static getNumberOfDecimals(value) {
        var string_value = new String(value);
        var elements = string_value.split('.');
        if (elements.length === 1) return 0;
        return elements[1].length;
    }
    
    /**
     * Get the thousand separator character
     * @returns {string}
     */
    static getThousandSeparator() {
        switch (Platform.NumberFormat.getNumberFormat()) {
            case 1:
                return '.';
            case 2:
                return ',';
            default:
                console.error('Unknown number format');
                console.log(Platform.NumberFormat.getNumberFormat());
                return '';
        }
    }
    
    /**
     * Get a formatted number from a float
     * @param {float} number The number
     * @param {int} decimals The number of decimals to display
     * @param {bool} separate_thousands True if a thousand separator should be drawn
     * @returns {string} The formatted number
     */
    static getFormattedNumber(number, decimals, separate_thousands) {
        if (number === null) return '';
        return number.toLocaleString(Platform.NumberFormat.getLocaleString(), {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
            useGrouping: separate_thousands === true
        });
    }
    
    /**
     * Get an unformatted number from a formatted number
     * @param {string} formatted_number The formatted number
     * @returns {float} The unformatted number
     */
    static getUnformattedNumber(formatted_number) {
        var number_string = new String(formatted_number);
        // Strip thousand separators and convert dot
        return parseFloat(number_string.replaceAll(Platform.NumberFormat.getThousandSeparator(), '').replace(Platform.NumberFormat.getDecimalSeparator(),'.'));
    }
}