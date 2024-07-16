$(function () {
    var additionalDeliveryWrapper = $('input[name^="AEUC_LABEL_DELIVERY_ADDITIONAL_"]').first().closest('.form-group').parent().closest('.form-group');

    $('[name="AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL"]').on('change', function () {
        if ($(this).val() == 0) {
            additionalDeliveryWrapper.hide();
        } else {
            additionalDeliveryWrapper.show();
        }
    });

    $('[name="AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL"]:checked').val() == 0 && additionalDeliveryWrapper.hide();
});
