$(function() {
    $('input[name="file"]').change(function() {
        $(this).closest('form').hide().submit();
        $('#upload_message').show();
    })
    
    $('#file_delete').click(function() {
        $('#file_delete_form').submit();
    }).css('cursor', 'pointer');
})