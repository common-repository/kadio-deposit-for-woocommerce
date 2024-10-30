<?php

$amount = get_option('kadio_deposit_settings_amount', false);
//if($amount) {}

$amount = intval($amount) * 0.01;
$has_cart = is_a(WC()->cart, 'WC_Cart');

?>

<div class="kadio_deposit_deposit">
    <span><?php echo __('Estimated deposit payment: ', 'kadio-deposit-for-woocommerce'); ?></span>
    <span><?php echo wp_kses(wc_price(WC()->cart->cart_contents_total * $amount), ['span' => []]); ?></span>
</div>