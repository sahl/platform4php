Platform.Time = class {
    
    /**
     * Convert a Platform date string on the form YYYY-MM-DD HH:MM:SS from UTC to the
     * local time zone (in the same form)
     * @param {string} date_string date in UTC
     * @returns {string} date in local time zone
     */
    static convertUTCToLocal(date_string) {
        if (! Platform.Time.getLocalTimeZone()) return date;
        var utc_date = new Date(date_string+' +0000');
        var local_date = utc_date.toLocaleString("da", {timeZone: Platform.Time.getLocalTimeZone(), year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit'});
        return Platform.Time.localeStringToPlatformString(local_date);
    }
    
    /**
     * Convert a Platform date string on the form YYYY-MM-DD HH:MM:SS from the local time zone
     * to UTC (in the same form)
     * @param {string} date_string date in local time zone
     * @returns {string} date in UTC
     */
    static convertLocalToUTC(date_string) {
        if (! Platform.Time.getLocalTimeZone()) return date;
        var local_date = new Date(date_string+' ('+Platform.Time.getLocalTimeZone()+')');
        var utc_date = local_date.toLocaleString("da", {timeZone: '+0000', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit'});
        return Platform.Time.localeStringToPlatformString(utc_date);
    }
    
    /**
     * Get the local timezone as passed from the backend
     * @returns {string}
     */
    static getLocalTimeZone() {
        return new String($('body').data('platform_time_zone'));
    }
    
    /**
     * Transform a string from the da locale to the Platform format
     * @param {string} date_string Danish locale string
     * @returns {string} Platform formatted string
     */
    static localeStringToPlatformString(date_string) {
        return date_string.replace(/(\d+)\.(\d+)\.(\d+), (\d+)\.(\d+)\.(\d+)/, "$3-$2-$1 $4:$5:$6");        
    }
}