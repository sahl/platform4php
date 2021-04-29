addCustomPlatformFunction(function(item) {
    platform_add_multiplier_functionality($('.platform_form_multiplier_element', item));
    
    $('.platform_sortable', item).sortable({
        stop: function() {
            platform_multiplier_fixnames($(this));
        },
        cancel: "input,textarea,button,select,option,.note-editor",
        items: "div.platform_form_multiplier_element:not(:last-child)"
    }).find('.platform_form_multiplier_element').css('cursor', 'move');
});



function platform_add_multiplier_functionality(element) {
    $(element).find('input[type!="checkbox"],textarea').keyup(platform_handle_multiplier_change);
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

function platform_handle_multiplier_change() {
    var row = $(this).closest('.platform_form_multiplier_element');
    // Check if we need to expand
    if ((row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)')) && $(this).val() != '') {
        // We need to expand.
        var new_row = row.clone();
        new_row.insertAfter(row);
        //new_row.appendTo($(this).closest('.platform_form_multiplier'));
        new_row.find('textarea,input[type!="checkbox"]').val('');
        new_row.find('input[type="checkbox"]').attr('checked', false);
        new_row.find('.formfield_error').removeClass('formfield_error');
        new_row.find('.formfield_error_container').hide();
        new_row.applyPlatformFunctions();
        platform_add_multiplier_functionality(new_row);
        row.closest('.platform_form_multiplier').trigger('row_added');
        platform_multiplier_fixnames($(this).closest('.platform_form_multiplier'));
        
        
    } else {
        // We need to remove this row if it's empty, except if it is the last row or the last row of it's kind
        if (($(this).val() == '' || $(this).is('[type="checkbox"]:not(:checked)')) && ! platform_detect_values(row) && ! (row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)'))) {
            var container = $(this).closest('.platform_form_multiplier');
            if (row.is(':last-child') || row.next().is(':not(.platform_form_multiplier_element)'))
                row.prev().find('input:first').focus();
            else
                row.next().find('input:first').focus();
            row.remove();
            platform_multiplier_fixnames(container);
            container.trigger('row_deleted');
        }
    }
    return true;
}

function platform_multiplier_fixnames(element) {
    // We need to determine what level we are operating on
    var level = 0;
    var parent_multiplier = element.parent();
    while (parent_multiplier.closest('.platform_form_multiplier').length) {
        parent_multiplier = parent_multiplier.closest('.platform_form_multiplier').parent();
        level++;
    }
    // Catch all before relevant counter
    var regexp_string = '/(.*?';
    while (level-- > 0) regexp_string += '\\[\\d*\\]\\[.*?\\]';
    regexp_string += ')';
    // Skip counter and get all following
    regexp_string += '\\[\\d*\\](\\.*)/i';
    
    var regexp = eval(regexp_string);
    var i = 0;
    element.children('.platform_form_multiplier_element').each(function() {
        $(this).find('input,select,textarea').each(function() {
            var name = $(this).prop('name');
            var new_name = name.replace(regexp, '$1['+i+']$2');
            var id = $(this).prop('id');
            var new_id = id.replace(regexp, '$1['+i+']$2');
            $(this).prop('name', new_name).prop('id', new_id);
            $(this).closest('.platform_form_field_container').find('label').prop('for', new_name);
        })
        $(this).find('.platform_formfield_container').each(function() {
            var id = $(this).prop('id');
            var new_id = id.replace(regexp, '$1['+i+']$2');
            $(this).prop('id', new_id);
            // TODO: We cannot find the main field for the label
        })
        $(this).find('iframe.file_select_frame').each(function() {
            var name = $(this).data('name');
            var new_name = name.replace(regexp, '$1['+i+']$2');
            var id = $(this).attr('id');
            var new_id = id.replace(regexp, '$1['+i+']$2');
            $(this).prop('id', new_id);
            // Recode url if source changed
            var src = '/Platform/Field/php/file.php?form_name='+$(this).closest('form').attr('id')+'&field_name='+new_name;
            if (name != new_name) $(this).prop('src', src);
        })
        i++;
    })
}