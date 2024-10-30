<?php

if (!class_exists('Kadio_Payments')) {
    class Kadio_Payments extends Kadio_Base_Controller
    {
        public function __construct()
        {
            // Filter to change stripe Request body
            add_filter('woocommerce_stripe_request_body', [$this, 'kadio_deposit_wc_stripe_update_checkout_amount'], 10, 2);
            //
            add_filter('payplug_gateway_payment_data', [$this, 'kadio_deposit_update_checkout_amount'], 10, 4);

            add_filter('ppcp_create_order_request_body_data', [$this, 'kadio_deposit_update_paypal_amount'], 10, 1);
            add_filter('ppcp_patch_order_request_body_data', [$this, 'kadio_deposit_update_paypal_amount_before_process'], 10, 1);
            //add_action('woocommerce_thankyou', [$this, 'custom_content_thankyou'], 10, 1);
        }

        public function kadio_deposit_update_paypal_amount($data)
        {
            
            if (!empty($data['purchase_units'][0]['invoice_id'])) {
                $order_id = intval(trim(strtolower($data['purchase_units'][0]['custom_id'])));
                $data['purchase_units'][0]['invoice_id'] = 'kdc-final-wc-' . $data['purchase_units'][0]['custom_id'];
                $order = wc_get_order($order_id);
                //$deposit = get_post_meta($order_id, 'kadio_deposit_new', true);
                //$deposit = $this->get_deposit_by_cart();
                $deposit = $this->get_deposit_by_order($order_id);
                $deposit_paid = boolval(get_post_meta($order_id, 'kadio_deposit_paid', true));

                $amount = $order->get_total();

                if ($deposit) {
                    $deposit_to_pay = doubleval(get_post_meta($order_id, 'kadio_deposit_paid_amount', true));
                    if ($deposit_paid) {
                        $data['purchase_units'][0]['amount']['value'] = $amount - $deposit_to_pay;
                    }
                }

                $total_line_total = 0;
                // Parcourez les éléments de commande
                foreach ($order->get_items() as $item_id => $item) {
                    // Ajoutez le line_total de chaque élément au total
                    $total_line_total += $item->get_total();
                }

                $total_line_total = doubleval(number_format(($total_line_total - $this->get_item_to_pay_by_order_without_shipping_cost($order, $deposit)), 2, '.', ''));

                $data['purchase_units'][0]['amount']['breakdown']['item_total']['value'] = $total_line_total;
                $data['purchase_units'][0]['amount']['breakdown']['shipping']['value'] = $this->get_paypal_remaining_deposit_of_shipping_cost_to_pay_by_order($order, $deposit);
                if (!empty($data['purchase_units'][0]['amount']['breakdown']['discount'])) {
                    $data['purchase_units'][0]['amount']['breakdown']['discount']['value'] = 0;
                }
                $data['purchase_units'][0]['items'] = $this->make_items_for_paypal_by_order($order, $data['purchase_units'][0]['items'], $deposit, true);
                return $data;
            }
            $deposit = sanitize_text_field($_POST['kadio_deposit']);
            if ($deposit == '1') {
                return $data;
            }
            $deposit = doubleval($deposit);

            $discount = 0;
            if (
                isset($data['purchase_units'][0]['amount']['breakdown']['discount']) &&
                !empty($data['purchase_units'][0]['amount']['breakdown']['discount']['value'])
            ) {
                $discount = doubleval($data['purchase_units'][0]['amount']['breakdown']['discount']['value']);
                $data['purchase_units'][0]['amount']['breakdown']['discount']['value'] = 0;
            }

            $data['purchase_units'][0]['amount']['value'] = $this->kadio_deposit_to_pay_by_cart(WC(), $deposit);
            $data['purchase_units'][0]['amount']['breakdown']['item_total']['value'] = $this->get_item_to_pay_by_cart_without_shipping_cost(WC(), $deposit);
            $data['purchase_units'][0]['amount']['breakdown']['shipping']['value'] = $this->get_deposit_of_shipping_cost_to_pay_by_cart(WC(), $deposit);
            $data['purchase_units'][0]['items'] = $this->make_items_for_paypal_by_cart($data['purchase_units'][0]['items'], $deposit);
            return $data;
        }
        public function kadio_deposit_update_paypal_amount_before_process($data)
        {
            if (!empty($data[0]['value']['invoice_id'])) {
                $order_id = intval(trim(strtolower($data[0]['value']['custom_id'])));
                $deposit = $this->get_deposit_by_order($order_id);
                $deposit_paid = boolval(get_post_meta($order_id, 'kadio_deposit_paid', true));
                $deposit_to_pay = doubleval(get_post_meta($order_id, 'kadio_deposit_paid_amount', true));
                $order = wc_get_order($order_id);
                if ($deposit == 1) {
                    return $data;
                }
                if ($deposit_paid) {
                    $data[0]['value']['invoice_id'] = 'kdc-final-wc-' . $data[0]['value']['custom_id'];
                    $data[0]['value']['amount']['value'] = (string) ($data[0]['value']['amount']['value'] - $deposit_to_pay);

                    $total_line_total = 0;
                    // Parcourez les éléments de commande
                    foreach ($order->get_items() as $item_id => $item) {
                        // Ajoutez le line_total de chaque élément au total
                        $total_line_total += $item->get_total();
                    }

                    $total_line_total = doubleval(number_format(($total_line_total - $this->get_item_to_pay_by_order_without_shipping_cost($order, $deposit)), 2, '.', ''));
                    //$data[0]['value']['amount']['breakdown']['item_total']['value'] = $order->get_subtotal() - $this->get_item_to_pay_by_order_without_shipping_cost($order, $deposit);
                    $data[0]['value']['amount']['breakdown']['item_total']['value'] = $total_line_total;
                    $data[0]['value']['amount']['breakdown']['shipping']['value'] = $this->get_paypal_remaining_deposit_of_shipping_cost_to_pay_by_order($order, $deposit);
                    if (!empty($data[0]['value']['amount']['breakdown']['discount'])) {
                        $data[0]['value']['amount']['breakdown']['discount']['value'] = 0;
                    }
                    $data[0]['value']['items'] = $this->make_items_for_paypal_by_order($order, $data[0]['value']['items'], $deposit, true);
                    return $data;
                }

                $data[0]['value']['amount']['value'] = $deposit_to_pay;

                $data[0]['value']['amount']['breakdown']['item_total']['value'] = $this->get_item_to_pay_by_cart_without_shipping_cost(WC(), $deposit);
                $data[0]['value']['amount']['breakdown']['shipping']['value'] = $this->get_deposit_of_shipping_cost_to_pay_by_cart(WC(), $deposit);
                if (!empty($data[0]['value']['amount']['breakdown']['discount'])) {
                    $data[0]['value']['amount']['breakdown']['discount']['value'] = 0;
                }
                $data[0]['value']['items'] = $this->make_items_for_paypal_by_order($order, $data[0]['value']['items'], $deposit);
            }
            return $data;
        }

        /**
         * Alter Stripe Payment amount according to selected deposit field
         *
         * @param $request
         * @param $api
         * @return mixed
         */

        //  Pay with stripe
        public function kadio_deposit_wc_stripe_update_checkout_amount($request, $api)
        {
            if (!isset($request['metadata'])) {
                return $request;
            }

            if (!isset($request['amount'])) {
                return $request;
            }


            $order_id = intval($request['metadata']['order_id']);

            $deposit = get_post_meta($order_id, 'kadio_deposit_new', true);

            $deposit_paid = get_post_meta($order_id, 'kadio_deposit_paid', true);

            // error_log('Request: ' . var_export($request, true));

            return $this->kadio_deposit_update_amount_with_deposit($order_id, $request, $request['amount'], doubleval($deposit), boolval($deposit_paid));
        }

        // Pay with payplug
        public function kadio_deposit_update_checkout_amount($payment_data, $order_id, $array, $address_data)
        {


            $deposit = get_post_meta($order_id, 'kadio_deposit_new', true);

            $deposit_paid = get_post_meta($order_id, 'kadio_deposit_paid', true);
            if (empty($deposit_paid)) {
                $deposit_paid = false;
            }


            /*$paymentData = $this->kadio_deposit_update_amount_with_deposit($order_id, $payment_data, $payment_data['amount'] / 100, doubleval($deposit), boolval($deposit_paid));
            $paymentData['amount'] *= 100;
            return $paymentData;*/
            return $this->kadio_deposit_update_amount_with_deposit($order_id, $payment_data, $payment_data['amount'], doubleval($deposit), boolval($deposit_paid));
        }

        private function kadio_deposit_update_amount_with_deposit($order_id, $payment_data, $amount, $deposit, $is_deposit_paid)
        {
            if ($deposit) {
                $order = wc_get_order($order_id);
                $deposit_to_pay = $this->kadio_deposit_to_pay_by_order($order, $deposit);

                if ($is_deposit_paid) {
                    // 2e deposit
                    $payment_data['amount'] = $amount - $deposit_to_pay * 100;
                } else {
                    // 1er deposit
                    $payment_data['amount'] = ($deposit_to_pay) * 100;
                }
            }
            return $payment_data;
        }

        private function make_items_for_paypal_by_cart(array $current_items, $deposit)
        {
            $cart = WC()->cart;
            $items = [];
            $is_product_deposit = $this->is_product_deposit();
            foreach ($current_items as $current_item) {
                $current_item['unit_amount']['value'] = $this->get_unit_product_deposit_amount_by_cart($cart->get_cart_item($current_item['cart_item_key']), $is_product_deposit, $deposit);
                $items[] = $current_item;
            }
            return $items;
        }

        private function get_unit_product_deposit_amount_by_cart($cart_item, $is_product_deposit, $deposit)
        {
            $deposit = doubleval($deposit);
            if ($is_product_deposit) {
                if (!$this->is_product_eligible($cart_item['product_id'])) {
                    //return (float)$cart_item['line_subtotal'] / (float)$cart_item['quantity'];
                    return (float) number_format((float)$cart_item['line_total'] / (float)$cart_item['quantity'], 2, '.', '');
                }
            }
            if ($deposit != 0) {
                //return ((float)$cart_item['line_subtotal'] / (float)$cart_item['quantity']) * $deposit;
                return (float) number_format(((float)$cart_item['line_total'] / (float)$cart_item['quantity']) * $deposit, 2, '.', '');
            }
            //return ((float)$cart_item['line_subtotal'] / (float)$cart_item['quantity']);
            return (float) number_format((float)$cart_item['line_total'] / (float)$cart_item['quantity'], 2, '.', '');
        }

        private function make_items_for_paypal_by_order($order, array $current_items, $deposit, $is_deposit_paid = false)
        {
            $order_items = $order->get_items();
            $items = [];

            $is_product_deposit = $this->is_product_deposit(false, $order->get_id());

            foreach ($order_items as $key => $order_item) {
                $product = $order_item->get_product();
                $category = ($product->is_virtual()) ? "DIGITAL_GOODS" : "PHYSICAL_GOODS";
                //$price = (float)$order_item->get_subtotal() / (float)$order_item->get_quantity();
                $price = (float) $order_item->get_total() / (float)$order_item->get_quantity();
                $description = $product->get_description();
                $description = strip_shortcodes(wp_strip_all_tags($description));
                $description = substr($description, 0, 127) ?: '';

                $data = [
                    "name" => mb_substr($product->get_name(), 0, 127),
                    "unit_amount" => [
                        'currency_code' => strtoupper($order->get_currency()),
                        'value' => $price,
                    ],
                    "quantity" => $order_item->get_quantity(),
                    "description" => $description,
                    "sku" => $product->get_sku(),
                    "category" => $category
                ];

                if ($is_deposit_paid) {
                    if ($is_product_deposit) {
                        if ($this->is_product_eligible_on_order($key)) {
                            $price_unit_deposit = $price * (doubleval($deposit));
                            $price = $price - $price_unit_deposit;
                            $data['unit_amount']['value'] = number_format($price, 2, '.', '');
                        } else {
                            $data['unit_amount']['value'] = 0;
                        }
                        $items[] = $data;
                        continue;
                    }
                    $price_unit_deposit = $price * doubleval($deposit);
                    $price = $price - $price_unit_deposit;
                    $data['unit_amount']['value'] = doubleval(number_format($price, 2, '.', ''));
                } else {
                    if ($is_product_deposit) {
                        if ($this->is_product_eligible_on_order($key)) {
                            $price_unit_deposit = doubleval(number_format(($price * doubleval($deposit)), 2, '.', ''));
                            $data['unit_amount']['value'] = $price_unit_deposit;
                        }
                        $items[] = $data;
                        continue;
                    }
                    $price_unit_deposit = doubleval(number_format(($price * doubleval($deposit)), 2, '.', ''));
                    $data['unit_amount']['value'] = $price_unit_deposit;
                }
                $items[] = $data;
            }
            return $items;
        }
        function custom_content_thankyou($order_id)
        {

            $deposit = get_post_meta($order_id, 'kadio_deposit_new', true);
            $order = wc_get_order($order_id);
            // $deposit_paid = boolval(get_post_meta($order_id, 'kadio_deposit_paid', true));
            $is_payplug_partial_payment = boolval(get_post_meta($order_id, "is_payplug_partial_payment", true));
            $is_payplug_payment = strtolower($order->get_payment_method()) === "payplug";

            if ($is_payplug_payment && $is_payplug_partial_payment) {
                $order->update_status('partial_payment');
                $order_status = $order->get_status();
            }
        }
    }
}
