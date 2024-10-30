<?php

if (!class_exists('Kadio_Cart')) {
    class Kadio_Cart extends Kadio_Base_Controller {
        public function __construct()
        {
            add_action('woocommerce_mini_cart_contents', [$this, 'kadio_deposit_menu_carts']);

            // add field in general product option
            add_action('woocommerce_add_to_cart', [$this, 'kadio_deposit_woocommerce_add_to_cart'], 10, 6);

            add_filter('woocommerce_get_item_data', [$this, 'kdc_display_custom_data_in_cart'], 10, 2);
            add_filter('woocommerce_locate_template', [$this, 'kdc_load_custom_review_order_template'], 10, 3);

            add_action("kadio_deposit_after_shipping_name", [$this, 'kdc_display_shipping_custom_data']);
        }

        public function kadio_deposit_menu_carts()
        {
            echo show_deposit_amount_view();
        }

        public function kadio_deposit_woocommerce_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
        {
            $product = wc_get_product($product_id);
            $is_deposit_illegible = $product->get_meta("kadio_deposit_add_product_custom_fields_deposit");
            $product->update_meta_data("kadio_deposit_add_product_custom_fields_deposit", $is_deposit_illegible);

            $this->refresh_session_data();
        }

        public function kdc_display_custom_data_in_cart($item_data, $cart_item)
        {
            if ($this->is_product_deposit() && $this->is_product_eligible($cart_item['product_id'])) {
                $item_data[] = [
                    'key' => __("Eligible", "kadio-deposit-for-woocommerce"),
                    'value' => __("Yes", "kadio-deposit-for-woocommerce")
                ];
            }

            return $item_data;
        }

        public function kdc_load_custom_review_order_template($template, $template_name, $template_path) {
            if ($template_name === 'cart/cart-shipping.php') {
                // Use the path to your custom template within your plugin
                $template = plugin_dir_path(dirname(__FILE__)) . 'templates/woocommerce/cart/cart-shipping.php';
            }
            return $template;
        }

        public function kdc_display_shipping_custom_data()
        {
            echo "<p style='font-size: 12px;'>";
            if ($this->is_deposit_on_shipping_cost()) {
                echo __('Eligible', 'kadio-deposit-for-woocommerce') . ': ' . __("Yes", "kadio-deposit-for-woocommerce");
            } else {
                echo __('Eligible', 'kadio-deposit-for-woocommerce') . ': ' . __("No", "kadio-deposit-for-woocommerce");
            }
            echo "</p>";
        }
    }
}