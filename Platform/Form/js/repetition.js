addCustomPlatformFunction(function(item) {
    $('.repetition_field', item).each(function() {
        var element = $(this);
        
        var user_change = true;
        
        $('.repetition_type', this).change(function() {
            $('.typesection', element).hide();
            $('.type'+$(this).val()).show();
            if (user_change) {
                $('input[type="checkbox"]', element).prop('checked', true);
                $('.typesection select', element).children('option:first-child').next().prop('selected', true);
            }
        });
        
        $('.month_type_radio', this).click(function() {
            $('.month_day_container select', element).prop('disabled', true);
            $(this).closest('.month_day_container').find('select').prop('disabled', false);
        });
        if (! $('.month_type_radio:checked', this).length) {
            $('.month_day_container select', element).prop('disabled', true);
        } else {
            $('.month_type_radio:checked', this).trigger('click');
        }
        
        user_change = false;
        $('.repetition_type', this).trigger('change');
        user_change = true;
    });
    
});

