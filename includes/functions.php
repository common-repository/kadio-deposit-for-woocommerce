<?php

if (!function_exists('kadio_deposit_load_view')) {
    function kadio_deposit_load_view (string $view, array $params = [])
    {
        ob_start();
        extract($params);
        require KDC_WD_BASE_PATH . "/templates/" . $view . '.php';
        return ob_get_clean();
    }
}

if (!function_exists('show_deposit_amount_view')) {
    function show_deposit_amount_view()
    {
       return kadio_deposit_load_view("deposit_amount_view");
    }
}

if (!function_exists('show_deposit_remaining_amount_view')) {
    function show_deposit_remaining_amount_view()
    {
        return kadio_deposit_load_view("deposit_remaining_amount_view");
    }
}

if (!function_exists('show_deposit_remaining_message_view')) {
    function show_deposit_remaining_message_view()
    {
        return kadio_deposit_load_view("deposit_remaining_message_view");
    }
}

if (!function_exists('show_admin_order_deposit_data')) {
    function show_admin_order_deposit_data($data)
    {
        return kadio_deposit_load_view("admin_order_deposit_data", $data);
    }
}

if (!function_exists('show_deposit_on_order_review')) {
    function show_deposit_on_order_review($data)
    {
        return kadio_deposit_load_view("deposit_on_order_review", $data);
    }
}

if (!function_exists('show_deposit_display_data')) {
    function show_deposit_display_data($data)
    {
        return kadio_deposit_load_view("deposit_display_data", $data);
    }
}

if (!function_exists('kdc_is_pay_by_deposit')) {
    function kdc_is_pay_by_deposit($order_id)
    {
        $paidByDeposit = get_post_meta($order_id, "kdc_is_paid_by_deposit", true);
        if (empty($paidByDeposit)) {
            return true;
        } else {
            return boolval($paidByDeposit);
        }
    }
}