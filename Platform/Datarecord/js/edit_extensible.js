$(function() {
    $('#datarecordextensiblefield_form_field_type').change(function() {
        if ($(this).val() == 500 || $(this).val() == 501)
            $('#datarecordextensiblefield_form_linked_class_container').show();
        else 
            $('#datarecordextensiblefield_form_linked_class_container').hide();
            
    }).trigger('change');
});