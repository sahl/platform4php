Platform.Form.RepetitionField = class extends Platform.Form.Field {
    
    initialize() {
        var element = this.dom_node;

        var user_change = true;

        $('.repetition_type', element).change(function() {
            $('.typesection', element).hide();
            $('.type'+$(this).val()).show();
            if (user_change) {
                $('input[type="checkbox"]', element).prop('checked', true);
                $('.typesection select', element).children('option:first-child').next().prop('selected', true);
            }
        });

        $('.month_type_radio', element).click(function() {
            $('.month_day_container select', element).prop('disabled', true);
            $(this).closest('.month_day_container').find('select').prop('disabled', false);
        });
        if (! $('.month_type_radio:checked', element).length) {
            $('.month_day_container select', element).prop('disabled', true);
        } else {
            $('.month_type_radio:checked', element).trigger('click');
        }

        user_change = false;
        $('.repetition_type', this).trigger('change');
        user_change = true;
    }
    
    clear() {
        this.dom_node.find('.repetition_interval').val(1);
        this.dom_node.find('.repetition_type').val('').trigger('change');
    }

    getValue() {
    }

    setValue(value) {
        var dom_node = this.dom_node;
        if (value == null) {
            this.clear();
            return;
        }
        dom_node.find('.repetition_interval').val(value.interval);
        dom_node.find('.repetition_type').val(value.type).trigger('change');
        if (value.metadata.weekdays) this.checkWithValues(dom_node.find('.weekdays'), value.metadata.weekdays);
        if (value.metadata.months) this.checkWithValues(dom_node.find('.months'), value.metadata.months);
        if (value.metadata.monthday) dom_node.find('.monthday').val(value.metadata.monthday);
        if (value.metadata.occurrence) dom_node.find('.occurrence').val(value.metadata.occurrence);
        if (value.metadata.weekday) dom_node.find('.weekday').val(value.metadata.weekday);
        if (value.metadata.day) dom_node.find('.day').val(value.metadata.day);
        if (value.metadata.month) dom_node.find('.month').val(value.metadata.month);
    }
    
    checkWithValues(element, ids) {
        var dom_node = element;
        dom_node.find('input[type="checkbox"]').prop('checked', false);
        $.each(ids, function(key, value) {
            dom_node.find('input[value="'+value+'"]').prop('checked', true);
        });
    }
    
}

Platform.Component.bindClass('platform_component_repetition_field', Platform.Form.RepetitionField);