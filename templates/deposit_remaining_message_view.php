<?php
$message = get_option('kadio_deposit_settings_sold_message', false);
if (!$message) {
    $message = __('You will be charged the rest to pay', 'kadio-deposit-for-woocommerce');
} else {
    $message = __($message, 'kadio-deposit-for-woocommerce');
}

if ($message) :;

    ?>

    <div style="border: solid 1px red; padding: 10px; margin: 20px 40% 20px 20px; background: #ff00001f; color: red;">
        <?php echo esc_html($message); ?>
    </div>

<?php

endif;