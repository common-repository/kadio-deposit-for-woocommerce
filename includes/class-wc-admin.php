<?php

if (!class_exists('Kadio_WC_Admin')) {
    class Kadio_WC_Admin {
        public function __construct()
        {
            add_action('init', [$this, 'kadio_deposit_init']);
            // Filter to add "Deposit setting" in woocommerce settings tabs
            add_filter('woocommerce_settings_tabs_array', [$this, 'kadio_deposit_add_section'], 50);
            // Add setting link to plugins details
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);

            add_action('woocommerce_settings_tabs_kadio_deposit_settings', [$this, 'kadio_deposit_settings_tab']);
            add_action('woocommerce_update_options_kadio_deposit_settings', [$this, 'kadio_deposit_update_settings']);

            // Add product custom "eligible" meta field
            add_action('woocommerce_product_options_general_product_data', [$this, 'kadio_deposit_add_product_custom_fields']);
            // Save product custom "eligible" meta field
            add_action('woocommerce_process_product_meta', [$this, 'kadio_deposit_save_product_custom_fields']);

            // Order status
            add_filter('wc_order_statuses', [$this, 'kadio_deposit_custom_order_status']);
            // Allow payment for custom unpaid orders
            add_filter('woocommerce_order_is_paid_statuses', [$this, 'remove_custom_status_has_paid_statues'], 10, 1);
            add_filter('woocommerce_order_is_pending_statuses', [$this, 'kdc_allow_payment_for_custom_unpaid'], 10, 1);
            add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'kdc_woocommerce_valid_order_statuses_for_payment'], 10, 2);
            add_filter('woocommerce_valid_order_statuses_for_payment_complete', [$this, 'kdc_woocommerce_valid_order_statuses_for_payment'], 10, 2);

            add_filter('woocommerce_my_account_my_orders_actions', [$this, 'kdc_update_pay_now_button_to_my_account_orders'], 10, 2);
        }

        public function kdc_update_pay_now_button_to_my_account_orders($actions, $order)
        {
            if ($order->get_status() === 'partial-payment') {
                $actions['pay'] = array(
                    'url'  => $order->get_checkout_payment_url() . "&order-pay=" . $order->get_id(),
                    'name' => __('Pay', 'woocommerce')
                );
            }
            return $actions;
        }

        /**
         * Add setting link to plugins details
         * @param array $links
         * @return array|string[]
         */
        public function plugin_action_links(array $links = []): array
        {
            $url = admin_url('admin.php?page=wc-settings&tab=kadio_deposit_settings');
            $plugin_links = [
                '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'kadio-deposit-for-woocommerce') . '</a>',
            ];

            return array_merge($plugin_links, $links);
        }

        /**
         * Method to add "Deposit setting" in woocommerce settings tabs
         * @param $setings_tabs
         * @return array
         */
        public function kadio_deposit_add_section($setings_tabs)
        {
            $setings_tabs['kadio_deposit_settings'] = __('Deposit', 'kadio-deposit-for-woocommerce');
            return $setings_tabs;
        }

        /**
         * @return void
         */
        public function kadio_deposit_settings_tab()
        {
            wp_kses_post(woocommerce_admin_fields($this->kadio_deposit_get_settings()));
        }

        /**
         * @return void
         */
        public function kadio_deposit_update_settings()
        {
            wp_kses_post(woocommerce_update_options($this->kadio_deposit_get_settings()));
        }

        /**
         * Get custom woocommerce setting fields
         * @return mixed
         */
        private function kadio_deposit_get_settings()
        {
            $settings = array(

                'section_title' => array(

                    'name' => __('Deposit settings', 'kadio-deposit-for-woocommerce'),

                    'type' => 'title',

                    'desc' => '',

                    'id' => 'kadio_deposit_settings_section_title'

                ),

                'select_deposit_for_product_or_total' => array(
                    'name' => __('Deposit level', 'kadio-deposit-for-woocommerce'),
                    'type' => 'select',
                    'options' => array(
                        'product' => __('Product', 'kadio-deposit-for-woocommerce'),
                        'total' => __('Cart total amount', 'kadio-deposit-for-woocommerce'),
                    ),
                    'id' => 'kadio_deposit_settings_select_deposit_for_product_or_total',
                    'desc' => __('Select where to apply deposit:on product or total', 'kadio-deposit-for-woocommerce'),
                ),
                'apply_deposit_on_shipping' => array(
                    'name' => __('Apply on shipping cost', 'kadio-deposit-for-woocommerce'),
                    'type' => 'radio',
                    'options' => array(
                        "yes" => __('Yes', 'kadio-deposit-for-woocommerce'),
                        "no" => __('No', 'kadio-deposit-for-woocommerce')
                    ),
                    'id' => "kadio_deposit_apply_on_shipping",
                    'desc' => __('Select Yes if you want to apply deposit on shipping cost', 'kadio-deposit-for-woocommerce'),
                ),

                'apply_deposit_when_using_coupon' => array(
                    'name' => __('Disable deposit when using coupon', 'kadio-deposit-for-woocommerce'),
                    'type' => 'radio',
                    'options' => array(
                        "yes" => __('Yes', 'kadio-deposit-for-woocommerce'),
                        "no" => __('No', 'kadio-deposit-for-woocommerce')
                    ),
                    'id' => "kadio_deposit_when_using_coupon_id",
                    'desc' => __('Select Yes if you want to disable deposit when coupon is used', 'kadio-deposit-for-woocommerce'),
                    'desc_tip' => __('If yes the deposit will be disabled when coupon is used. If no, the deposit will be applied automatically on cart total when coupon is used.', 'kadio-deposit-for-woocommerce'),
                ),

                'amount' => array(

                    'name' => __('Amount', 'kadio-deposit-for-woocommerce'),

                    'type' => 'number',

                    //            'options' => array(

                    //                'min'  => '0',

                    //                'max'  => '99',

                    //                'step'  => '1',

                    //            ),

                    'desc_tip' => __('Set amount deposit in percentage. Default value 50%', 'kadio-deposit-for-woocommerce'),

                    'id' => 'kadio_deposit_settings_amount',
                    'default' => 50

                ),

                'show_deposit_details' => array(

                    'name' => __('Show deposit in order details', 'kadio-deposit-for-woocommerce'),

                    'desc_tip' => __('If checked, customer will see deposit details in order details', 'kadio-deposit-for-woocommerce'),

                    'id' => 'kadio_deposit_settings_show_details',

                    'type' => 'checkbox',

                    'css' => 'min-width:300px;',

                    'desc' => __('Enable to show details', 'kadio-deposit-for-woocommerce'),

                ),

                'show_deposit_mail' => array(

                    'name' => __('Show deposit in order email', 'kadio-deposit-for-woocommerce'),

                    'desc_tip' => __('If checked, customer will see deposit details in order confirmation email', 'kadio-deposit-for-woocommerce'),

                    'id' => 'kadio_deposit_settings_show_email',

                    'type' => 'checkbox',

                    'css' => 'min-width:300px;',

                    'desc' => __('Enable to show details', 'kadio-deposit-for-woocommerce'),

                ),

                'sold_message' => array(

                    'name' => __('Payment comment', 'kadio-deposit-for-woocommerce'),

                    'type' => 'textarea',

                    'desc_tip' => __('Show message when customer pay sold from his account', 'kadio-deposit-for-woocommerce'),

                    'id' => 'kadio_deposit_settings_sold_message',
                    'default' => __('Comment message', 'kadio-deposit-for-woocommerce')

                ),

                'payment_title' => [
                    'name' => __('Payment options title', 'kadio-deposit-for-woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('Ease of payment', 'kadio-deposit-for-woocommerce'),
                    'id' => 'kadio_deposit_settings_payment_title',
                    'default' => __('Payment facilities', 'kadio-deposit-for-woocommerce')
                ],

                'payment_cash_title' => [
                    'name' => __('Payment by cash label', 'kadio-deposit-for-woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('Ease of payment', 'kadio-deposit-for-woocommerce'),
                    'id' => 'kadio_deposit_settings_payment_cash_title',
                    'default' => __('Cash', 'kadio-deposit-for-woocommerce') // Comptant
                ],

                'payment_message_deposit_title' => [
                    'name' => __('Payment by deposit label', 'kadio-deposit-for-woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('Ease of payment', 'kadio-deposit-for-woocommerce'),
                    'id' => 'kadio_deposit_settings_payment_deposit_title',
                    'default' => __('Deposit of %s', 'kadio-deposit-for-woocommerce') //Acompte de %s
                ],

                'section_end' => array(

                    'type' => 'sectionend',

                    'id' => 'wc_settings_tab_demo_section_end'

                )

            );

            return apply_filters('wc_settings_tab_demo_settings', $settings);
        }

        /**
         * Add product custom "eligible" meta field
         * @return void
         */
        public function kadio_deposit_add_product_custom_fields()
        {
            global $woocommerce, $post;
            echo '<div class="option_group ">';
            woocommerce_wp_select(
                array(
                    'label' => __('Elligible deposit', 'kadio-deposit-for-woocommerce'),
                    'value' => get_post_meta(get_the_ID(), 'kadio_deposit_add_product_custom_fields_deposit', true),
                    'options' => array(
                        'no' => __('No', 'kadio-deposit-for-woocommerce'),
                        'yes' => __('Yes', 'kadio-deposit-for-woocommerce'),
                    ),
                    'desc' => __('Set if you want to activate a deposit for this product', 'kadio-deposit-for-woocommerce'),
                    'desc_tip' => __('Set if you want to activate a deposit for this product', 'kadio-deposit-for-woocommerce'),
                    'id' => 'kadio_deposit_add_product_custom_fields_deposit',
                )
            );
            echo '</div>';
        }

        /**
         * Save product custom "eligible" meta field
         * @param $post_id
         * @return void
         */
        public function kadio_deposit_save_product_custom_fields($post_id)
        {
            if (isset($_POST['kadio_deposit_add_product_custom_fields_deposit'])) {
                update_post_meta($post_id, 'kadio_deposit_add_product_custom_fields_deposit', sanitize_text_field($_POST['kadio_deposit_add_product_custom_fields_deposit']));
            }
        }

        public function kadio_deposit_custom_order_status($order_statuses)
        {
            $order_statuses["wc-partial-payment"] = _x("Partial payment", 'Order status', 'kadio-deposit-for-woocommerce');

            return $order_statuses;
        }

        public function kadio_deposit_init()
        {
            register_post_status('wc-partial-payment', array(
                'label' => _x("Partial payment", 'Order status', 'kadio-deposit-for-woocommerce'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Partial payment <span class="count"> (%s) </span>',  'Partial payments <span class="count"> (%s) </span>', 'kadio-deposit-for-woocommerce')
            ));
        }

        public function kdc_allow_payment_for_custom_unpaid($statuses)
        {
            $statuses[] = "partial-payment";
            return $statuses;
        }

        public function remove_custom_status_has_paid_statues($statuses)
        {
            unset($statuses['partial-payment']);
            return $statuses;
        }

        public function kdc_woocommerce_valid_order_statuses_for_payment($statues, $order)
        {
            $statues[] = "partial-payment";
            return $statues;
        }
    }
}