<?php
//echo '<p>'.__('Thank you for your order, please click the button below to complete payment.', 'woocommerce').'</p>';
?>
<form id="hitpay" action="<?php echo $endpoint ?>" method="POST" name="process" data-test="test"
      style="display:none !important;">
    <?php foreach ($params as $key => $val): ?>
        <input type='hidden' name="<?php echo $key ?>" value="<?php echo $val ?>"/>
    <?php endforeach; ?>

    <input type="submit" class="button-alt button" id="submit_hitpay_payment_form"
           value="<?php echo __('Complete Secure Payment Checkout with HitPay', 'woocommerce'); ?>"/>
    <a class="button cancel"
       href="<?php echo esc_url($order->get_cancel_order_url()) ?>"><?php echo __('Cancel Order', 'woocommerce'); ?></a>
</form>

<script type="text/javascript">
    jQuery(document).ready(function ($) {

//Hide order details (not needed, will redirect anyway)
        $('#content .order_details').hide();

//Submit form in the background to remove the extra click from the user
        var targetForm = $("#hitpay");
        console.log("--> Silently submit form");
        targetForm.submit();

    });
    // end of jQuery(document).ready
</script>