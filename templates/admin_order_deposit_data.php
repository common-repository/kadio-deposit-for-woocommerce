<?php

if ($deposit && $deposit != '1') :

    ?>

    <tr>

        <td class="label"><?php echo __('Deposit paid: ', 'kadio-deposit-for-woocommerce'); ?></td>

        <td width="1%"></td>

        <td class="total"><?php echo wp_kses(wc_price($deposit_to_pay), ['span' => []]); ?></td>

    </tr>

    <tr>

        <td class="label"><?php echo __('Remains to be paid: ', 'kadio-deposit-for-woocommerce'); ?></td>

        <td width="1%"></td>

        <td class="total"><?php echo wp_kses(wc_price($order->get_data()['total'] - $deposit_to_pay), ['span' => []]); ?></td>

    </tr>

<?php endif;