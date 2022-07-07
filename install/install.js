$(function() {
    $('#install_form_mail_type').change(function() {
        if ($(this).val()) {
            $('#mail_section').show();
        } else {
            $('#mail_section').hide();
        }
        if ($(this).val() == 'smtp') {
            $('#smtp_section').show();
        } else {
            $('#smtp_section').hide();
        }
    }).trigger('change');
})