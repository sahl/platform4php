$(function() {
    $('#install_form_mail_type').change(function() {
        if ($(this).val() == 'smtp') {
            $('#smtp_section').show();
        } else {
            $('#smtp_section').hide();
        }
    }).trigger('change');
})