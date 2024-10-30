<?php

if (isset($_GET['key']) && !empty($_GET['key'])) {
    // sanitize_key
    $orderKey = sanitize_key($_GET['key']);
    $orderId = wc_get_order_id_by_order_key($orderKey);
    $order = wc_get_order($orderId);

    if ($order) {
        $deposit_paid_amount = get_post_meta($orderId, 'kadio_deposit_paid_amount', true);
        $total = $order->get_data()['total'] - doubleval($deposit_paid_amount);
        ?>
        <div style="padding-top: 50px; padding-bottom: 50px; text-align: right;">
            <span style="color: #00AB7A; font-size: 18px; font-weight: bold;">
                <?php echo __('Amount to pay : ', 'kadio-deposit-for-woocommerce'); ?>
                <?php echo wp_kses(wc_price($total), ['span' => []]); ?>
            </span>
        </div>
        <?php
    }
}