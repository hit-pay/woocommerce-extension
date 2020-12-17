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
/*    jQuery(document).ready(function ($) {
        $('#content .order_details').hide();
        var targetForm = $("#hitpay");
        console.log("--> Silently submit form");
        targetForm.submit();
    });*/

    document.addEventListener('DOMContentLoaded', function(){
        var block = null;
        var targetForm = null;
        //Hide order details (not needed, will redirect anyway)
        if (block = document.querySelector("#content .order_details")) {
            block.style.display = 'none';
        }
        //Submit form in the background to remove the extra click from the user
        if((targetForm = document.querySelector("#hitpay")) !== null) {
            console.log("--> Silently submit form");
            targetForm.submit()
        }
    });
</script>