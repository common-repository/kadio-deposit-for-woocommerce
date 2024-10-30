
<?php
if ($deposit != "1") {
    ?>
    <div style="font-weight: 700;">

        <dt style="display: inline-block; float: left;"><?php echo __('Deposit paid: ', 'kadio-deposit-for-woocommerce'); ?></dt>

        <dd style="text-align: right;"><?php echo wp_kses(wc_price($deposit_to_pay), ['span' => []]); ?></dd>

        <dt style="display: inline-block; float: left;"><?php echo __('Remains to be paid: ', 'kadio-deposit-for-woocommerce'); ?></dt>

        <dd style="text-align: right;"><?php echo wp_kses(wc_price($order->get_data()['total'] - $deposit_to_pay), ['span' => []]); ?></dd>

    </div>
    <?php
} else {
    if (kdc_is_pay_by_deposit($order->get_id())) {
        
        // When payment is done without deposit
        $paidWithoutDeposit = boolval(get_post_meta($order->get_id(), "kdc_without_deposit", true));

        $deposit_paid_amount = $order->get_meta('kadio_deposit_paid_amount', true);
        ?>
        
        <div style="font-weight: 700;">

        <?php //if ($deposit_paid_amount) //old code : ?>
            <?php if (!$paidWithoutDeposit) : ?>
                <dt style="display: inline-block; float: left;"><?php echo __('Paid: ', 'kadio-deposit-for-woocommerce'); ?></dt>

                <dd style="text-align: right;"><?php echo wp_kses(wc_price($order->get_data()['total'] - $deposit_paid_amount), ['span' => []]); ?></dd>

                <dt style="display: inline-block; float: left;"><?php echo __('Deposit paid: ', 'kadio-deposit-for-woocommerce'); ?></dt>

                <dd style="text-align: right;"><?php echo wp_kses(wc_price($deposit_paid_amount), ['span' => []]); ?></dd>
            <?php else : ?>
                <dt style="display: inline-block; float: left;"><?php echo __('Paid: ', 'kadio-deposit-for-woocommerce'); ?></dt>

                <dd style="text-align: right;"><?php echo wp_kses(wc_price($order->get_data()['total']), ['span' => []]); ?></dd>
            <?php endif; ?>

            <dt style="display: inline-block; float: left;"><?php echo __('Remains to be paid: ', 'kadio-deposit-for-woocommerce'); ?></dt>

            <dd style="text-align: right;"><?php echo wp_kses(wc_price(0), ['span' => []]); ?></dd>

        </div>
        <?php
    }
}