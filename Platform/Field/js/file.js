$(function() {
    $('input[name="file"]').change(function() {
        $(this).closest('form').submit();
    })
    
    $('#delete_current_file').click(function() {
        $('#delete_current_file_form').submit();
    }).css('cursor', 'pointer');
})