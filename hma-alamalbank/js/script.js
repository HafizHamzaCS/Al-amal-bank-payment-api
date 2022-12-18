jQuery(document).ready(function($) {

    jQuery(document).on('change', "#p_form_account_id", function() {
        console.log('p_form_account_id: ', $('input#p_form_account_id').val());
        if ($('#p_form_account_id').val() !== '') {
            $('#p_conferm_code').val(0);
            var form_data = new FormData(jQuery('form.checkout.woocommerce-checkout')[0]);
            form_data.append("action", 'hma_account_id_action');
            jQuery.ajax({
                url: alamal.ajax_url,
                type: 'POST',
                data: form_data,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('.hma_confirm_account_msg').hide()
                    $('.hma_confirm_account_ajax_loader').show()
                },
                success: function(response) {
                    if (response.msg_code == "006") {
                        $('.hma_confirm_account_msg').removeClass('hma_confirm_account_msg_error').addClass('hma_confirm_account_ms_success')
                    } else {
                        $('.hma_confirm_account_msg').removeClass('hma_confirm_account_ms_success').addClass('hma_confirm_account_msg_error')
                    }
                    $('.hma_confirm_account_msg').show().html(response.msg )
                    $('.form-row-p_conferm_code').show();
                    $('.hma_confirm_account_ajax_loader').hide();
                    $('#p_conferm_code').val('');
                },
            });
        }
    });
    /****************** */
    $(document).on('click', '.hma_resend_alamal_confirmation_code', function(e) {
        e.preventDefault();
        $('input#p_form_account_id').trigger('change')
        $('#p_conferm_code').val(0);
    })
});