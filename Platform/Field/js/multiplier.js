addCustomPlatformFunction(function(item) {
    platform_add_multiplier_functionality($('.platform_form_multiplier_element', item));
    
    $('.platform_sortable', item).sortable({
        stop: function() {
            platform_multiplier_fixnames($(this));
        },
        items: "div.platform_form_multiplier_element:not(:last-child)"
    }).find('.platform_form_multiplier_element').css('cursor', 'move');
});



function platform_add_multiplier_functionality(element) {
    $(element).find('input,textarea').blur(platform_handle_multiplier_change);
    $(element).find('input,textarea').keyup(platform_handle_multiplier_expand);
    $(element).find('input[type="checkbox"]').click(platform_handle_multiplier_change);
    $(element).find('select').change(platform_handle_multiplier_change);
}

function platform_detect_values(element) {
    var result = false;
    $('input[type!="hidden"],select,textarea', element).each(function() {
        if ($(this).is('[type="checkbox"]')) {
            if ($(this).is(':checked')) {
                result = true;
                return false;
            }
        } else if ($(this).val()) {
            result = true;
            return false;
        }
    });
    return result;
}

function platform_handle_multiplier_expand() {
    var row = $(this).closest('.platform_form_multiplier_element');
    // Check if we need to expand
    if (row.is(':last-child') && $(this).val() != '') {
        // We need to expand.
        var new_row = row.clone();
        new_row.appendTo($(this).closest('.platform_form_multiplier'));
        new_row.find('textarea,input[type!="checkbox"]').val('');
        new_row.find('input[type="checkbox"]').attr('checked', false);
        new_row.applyPlatformFunctions();
        platform_add_multiplier_functionality(new_row);
        row.closest('.platform_form_multiplier').trigger('row_added');
        platform_multiplier_fixnames($(this).closest('.platform_form_multiplier'));
    }    
}

function platform_handle_multiplier_change() {
    var row = $(this).closest('.platform_form_multiplier_element');
    // Check if we need to expand
    if (row.is(':last-child') && $(this).val() != '') {
        // We need to expand.
        var new_row = row.clone();
        new_row.appendTo($(this).closest('.platform_form_multiplier'));
        new_row.find('textarea,input[type!="checkbox"]').val('');
        new_row.find('input[type="checkbox"]').attr('checked', false);
        platform_add_multiplier_functionality(new_row);
        row.closest('.platform_form_multiplier').trigger('row_added');
        platform_multiplier_fixnames($(this).closest('.platform_form_multiplier'));
    } else {
        // Check if we need to collapse
        if (($(this).val() == '' || $(this).is('[type="checkbox"]:not(:checked)')) && ! platform_detect_values(row) && ! row.is(':last-child')) {
            var container = $(this).closest('.platform_form_multiplier');
            if (row.is(':last-child'))
                row.prev().find('input').focus();
            else
                row.next().find('input').focus();
            row.remove();
            platform_multiplier_fixnames(container);
            container.trigger('row_deleted');
        }
    }
}

function platform_multiplier_fixnames(element) {
    var i = 0;
    var regexp = /(.*)\[\d*\](\[.*\])/i;
    element.find('.platform_form_multiplier_element').each(function() {
        $(this).find('input,select,textarea').each(function() {
            var name = $(this).prop('name');
            var new_name = name.replace(regexp, '$1['+i+']$2');
            $(this).prop('name', new_name).prop('id', new_name);
            if ($(this).parent().is('.formfield_container')) {
                $(this).parent().prop('id', new_name+'_container');
                $(this).parent().find('label').prop('for', new_name);
            }
        })
        i++;
    })
}