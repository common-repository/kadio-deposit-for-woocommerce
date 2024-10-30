<?php

if (!class_exists('Kadio_Base_Controller')) {
    class Kadio_Base_Controller {

        /**
         * Get full deposit to pay by order with shipping cost
         *
         * @param $order
         * @param $deposit
         * @return float|int
         */
        protected function kadio_deposit_to_pay_by_order($order, $deposit)
        {
            $deposit = doubleval($deposit);
            $deposit_to_pay = $this->get_item_to_pay_by_order_without_shipping_cost($order, $deposit);

            $kadio_deposit_apply_on_shipping = get_post_meta($order->get_id(),'kadio_deposit_apply_on_shipping', true) ?? 'yes';
            if ($kadio_deposit_apply_on_shipping == 'yes') {
                $deposit_to_pay += doubleval($order->get_shipping_total()) * $deposit;
            } else {
                $deposit_to_pay += doubleval($order->get_shipping_total());
            }

            return $deposit_to_pay;
        }

        /**
         * Get full deposit to pay by cart with shipping cost
         *
         * @param $wc_object
         * @param $deposit
         * @return float|int
         */
        protected function kadio_deposit_to_pay_by_cart($wc_object, $deposit)
        {
            //error_log("BY CART DEPOSIT = " . var_export($deposit, true));
            return $this->get_item_to_pay_by_cart_without_shipping_cost($wc_object, $deposit) + $this->get_deposit_of_shipping_cost_to_pay_by_cart($wc_object, $deposit);
        }

        /**
         * Get items cost to pay by cart
         *
         * @param $wc_object
         * @param $deposit
         * @return float|int
         */
        protected function get_item_to_pay_by_cart_without_shipping_cost($wc_object, $deposit)
        {
            $kadio_deposit_session_cart_items = $wc_object->session->get('kadio_deposit_session_cart_items');
            //$deposit = $kadio_deposit_session_cart_items['deposit'];
            $kadio_deposit_for_product_or_total = $kadio_deposit_session_cart_items['kadio_deposit_for_product_or_total'];
            $kadio_deposit_apply_on_shipping = $kadio_deposit_session_cart_items['kadio_deposit_apply_on_shipping'];
            $deposit = doubleval($deposit);

            $cart = $wc_object->cart->get_cart();

            // $cart = WC()->cart->get_cart();
            $deposit_to_pay = 0;

            // Coupon
//            $coupons = $wc_object->cart->get_coupons();
//            $coupon_discount_amount = $this->get_coupon_discount_amount(null, $cart);
//
//            if ($coupons && $coupon_discount_amount >= 0) {
//                $coupon = true;
//            } else {
//                $coupon = false;
//            }

            if ($this->is_product_deposit()) {
                foreach ($cart as $key => $cart_item) {
                    if ($this->is_product_eligible($cart_item['data']->get_id())) {
                        $deposit_to_pay += ($cart_item['line_total'] * $deposit);
                    } else {
                        $deposit_to_pay += $cart_item['line_total'];
                    }
//                    $product = wc_get_product($cart_item['data']->get_id());
//                    // $is_deposit_illegible = $product->get_meta("kadio_deposit_add_product_custom_fields_deposit");
//                    $is_illegible = $wc_object->session->get('is_deposit_illegible_' . $product->get_id());
//                    $is_deposit_illegible = $is_illegible['is_illegible'];
//                    $price_unit = $product->get_price();
//                    $quantity = $cart_item['quantity'];
//                    $price = $price_unit * $quantity;
//                    if ($is_deposit_illegible == 'yes' && $deposit != 0) {
//                        $deposit_to_pay += $coupon ? $price : $price * $deposit;
//                    } else {
//                        $deposit_to_pay += $price;
//                    }
                }
                return $deposit_to_pay;
            } else {
                // $deposit_to_pay = WC()->cart->total * $deposit;
                foreach ($cart as $key => $cart_item) {
                    /*$product = wc_get_product($cart_item['data']->get_id());

                    $price_unit = $product->get_price();
                    $quantity = $cart_item['quantity'];
                    $price = $price_unit * $quantity;
                    if ($deposit != 0) {
                        $deposit_to_pay += $coupon ? $price : $price * $deposit;
                    } else {
                        $deposit_to_pay += $price;
                    }*/
                    $deposit_to_pay += $cart_item['line_total'];
                }
                return doubleval($deposit_to_pay * $deposit);
            }
        }

        /**
         * Method to get shipping cost by cart data
         *
         * @param $wc_object
         * @param $deposit
         * @return float|int
         */
        protected function get_deposit_of_shipping_cost_to_pay_by_cart($wc_object, $deposit): float|int
        {
            $kadio_deposit_session_cart_items = $wc_object->session->get('kadio_deposit_session_cart_items');
            $kadio_deposit_apply_on_shipping = $kadio_deposit_session_cart_items['kadio_deposit_apply_on_shipping'];
            $deposit = doubleval($deposit);

            // $cart = WC()->cart->get_cart();
            $deposit_to_pay = 0;

            if ($kadio_deposit_apply_on_shipping == 'yes' && $deposit != 0) {
                $deposit_to_pay += doubleval($wc_object->cart->get_shipping_total()) * $deposit;
            } else {
                $deposit_to_pay += doubleval($wc_object->cart->get_shipping_total());
            }

            return $deposit_to_pay;
        }

        /**
         * Get items cost to pay by order
         *
         * @param $order
         * @param $deposit
         * @return float|int
         */
        protected function get_item_to_pay_by_order_without_shipping_cost($order, $deposit)
        {
            $order_items = $order->get_items();
            $deposit_to_pay = 0;

            // Coupon
            $coupons = $order->get_coupon_codes();
            $coupon_discount_amount = $this->get_coupon_discount_amount($order->get_id(), null);

            if ($coupons && $coupon_discount_amount >= 0) {
                $coupon = true;
            } else {
                $coupon = false;
            }

            if ($this->is_product_deposit(false, $order->get_id())) {
                foreach ($order_items as $key => $order_item) {
                    //$product_id = $order_item->get_product_id();
                    $price_unit = doubleval($order_item->get_total());

                    // $quantity = $cart_item->get_quantity();
                    // $price = $price_unit * $quantity;
                    $price = $price_unit;

                    /*$is_deposit_illegible = wc_get_order_item_meta($key, 'kadio_deposit_illegible');
                    if ($is_deposit_illegible == 'yes') {
                        $deposit_to_pay += $price * (doubleval($deposit));
                    } else {
                        $deposit_to_pay += $price;
                    }*/

                    if ($this->is_product_eligible_on_order($key)) {
                        // $deposit_to_pay += $price * (doubleval($deposit));
                        $deposit_to_pay += $coupon ? $price : $price * (doubleval($deposit));

                    } else {
                        $deposit_to_pay += $price;
                    }
                }
            } else {
                foreach ($order_items as $key => $order_item) {
                    $price_unit = doubleval($order_item->get_total());
                    // $quantity = $cart_item->get_quantity();
                    // $price = $price_unit * $quantity;
                    $price = $price_unit;
                    // $deposit_to_pay += $price * doubleval($deposit);
                    $deposit_to_pay += $coupon ? $price : $price * (doubleval($deposit));

                }
            }

            $deposit_to_pay = $coupon ? $deposit_to_pay  * (doubleval($deposit)) : $deposit_to_pay;
            return $deposit_to_pay;
        }

        /**
         * Method to get shipping cost by order data
         *
         * @param $order
         * @param $deposit
         * @return float|int
         */
        protected function get_paypal_remaining_deposit_of_shipping_cost_to_pay_by_order($order, $deposit): float|int
        {
            $kadio_deposit_apply_on_shipping = get_post_meta($order->get_id(),'kadio_deposit_apply_on_shipping', true) ?? 'yes';

            $deposit_to_pay = 0;
            if ($kadio_deposit_apply_on_shipping == 'yes') {
                $deposit_to_pay += doubleval($order->get_shipping_total()) - (doubleval($order->get_shipping_total()) * $deposit);
            }

            return $deposit_to_pay;
        }

        /**
         * Method to get title of payment deposit case
         *
         * @return string
         */
        public function kadio_deposit_account_to_pay_title($option = null): string
        {
            $kadio_deposit_session_cart_items = WC()->session->get('kadio_deposit_session_cart_items');
            $kadio_deposit_for_product_or_total = $kadio_deposit_session_cart_items['kadio_deposit_for_product_or_total'];

            if($option =="coupon"){
                $kadio_deposit_for_product_or_total_title = __('Deposit to pay (applied to total cart)', 'kadio-deposit-for-woocommerce');
            
            }else{
                if ($kadio_deposit_for_product_or_total == 'product') {
                    $kadio_deposit_for_product_or_total_title = __('Deposit to pay (applied to products)', 'kadio-deposit-for-woocommerce');
                } else {
                    $kadio_deposit_for_product_or_total_title = __('Deposit to pay', 'kadio-deposit-for-woocommerce');
                }
            }


            return $kadio_deposit_for_product_or_total_title;
        }

        /**
         * Method to check if we are in product deposit case
         *
         * @param bool $on_cart
         * @param null $order_id
         * @return bool
         */
        public function is_product_deposit(bool $on_cart = true, $order_id = null): bool
        {
            return $this->get_deposit_level($on_cart, $order_id) === 'product';
        }

        public function get_deposit_level(bool $on_cart = true, $order_id = null): string
        {
            if ($on_cart) {
                $session = WC()->session;
                if ($session) {
                    $kadio_deposit_session_cart_items = $session->get('kadio_deposit_session_cart_items');
                    return strtolower($kadio_deposit_session_cart_items['kadio_deposit_for_product_or_total']);
                }
            } else {
                if (!is_null($order_id)) {
                    $kadio_deposit_for_product_or_total = get_post_meta($order_id,'kadio_deposit_for_product_or_total', true);
                    return strtolower($kadio_deposit_for_product_or_total);
                }
            }

            return strtolower(get_option('kadio_deposit_settings_select_deposit_for_product_or_total', 'total'));
        }

        /**
         * @param bool $on_cart
         * @param $order_id
         * @return string|null
         */
        public function get_deposit_shipping_method_application(bool $on_cart = true, $order_id = null):?string
        {
            if ($on_cart) {
                $session = WC()->session;
                if ($session) {
                    $kadio_deposit_session_cart_items = $session->get('kadio_deposit_session_cart_items');
                    return strtolower($kadio_deposit_session_cart_items['kadio_deposit_apply_on_shipping']);
                }
            } else {
                if (!is_null($order_id)) {
                    $kadio_deposit_apply_on_shipping = get_post_meta($order_id,'kadio_deposit_apply_on_shipping', true);
                    return strtolower($kadio_deposit_apply_on_shipping);
                }
            }

            return strtolower(get_option('kadio_deposit_apply_on_shipping', 'yes'));
        }

        /**
         * Method to check if deposit is available on shipping cost
         *
         * @return bool
         */
        public function is_deposit_on_shipping_cost(): bool
        {
            $session = WC()->session;
            if ($session) {
                $kadio_deposit_session_cart_items = $session->get('kadio_deposit_session_cart_items');
                if (!is_null($kadio_deposit_session_cart_items)) {
                    return strtolower($kadio_deposit_session_cart_items['kadio_deposit_apply_on_shipping']) === "yes";
                }
            }
            $kadio_deposit_apply_on_shipping = strtolower(get_option('kadio_deposit_apply_on_shipping', 'yes'));
            $this->refresh_session_data([
                'kadio_deposit_apply_on_shipping' => $kadio_deposit_apply_on_shipping
            ]);
            return $kadio_deposit_apply_on_shipping  === 'yes';
        }

        /**
         * Method to check if product is eligible for deposit
         *
         * @param $product_id
         * @return bool
         */
        public function is_product_eligible($product_id): bool
        {
            $session = WC()->session;
            if ($session) {
                $is_illegible = $session->get('is_deposit_illegible_' . $product_id);
                $is_deposit_illegible = $is_illegible['is_illegible'];

                return strtolower($is_deposit_illegible) === "yes";
            }
            $is_deposit_illegible = get_post_meta($product_id, "kadio_deposit_add_product_custom_fields_deposit", true);
            if (empty($is_deposit_illegible)) {
                $is_deposit_illegible = 'non';
            }
            return strtolower($is_deposit_illegible) === 'yes';
        }
        public function is_product_eligible_on_order($order_item_key): bool
        {
            $is_deposit_illegible = wc_get_order_item_meta($order_item_key, 'kadio_deposit_illegible');
            return strtolower($is_deposit_illegible) === 'yes';
        }

        /**
         * Method to refresh all deposit data on session
         *
         * @param $data
         * @return void
         */
        protected function refresh_session_data($data = [])
        {
            $cart = WC()->cart->get_cart();
            if (empty($data)) {
                $data = array(
                    'kadio_deposit_for_product_or_total' => get_option('kadio_deposit_settings_select_deposit_for_product_or_total'),
                    'kadio_deposit_for_product_or_total_temp' => get_option('kadio_deposit_settings_select_deposit_for_product_or_total'),
                    'kadio_deposit_apply_on_shipping' => get_option('kadio_deposit_apply_on_shipping'),
                    'deposit' => get_option('kadio_deposit_settings_amount'),
                    'deposit_temp' => get_option('kadio_deposit_settings_amount'),
                    'kadio_deposit_when_using_coupon' => get_option('kadio_deposit_when_using_coupon_id'),
                    // 'is_deposit_illegible_' . $product_id => $is_deposit_illegible,
                    'payment_title' => get_option('kadio_deposit_settings_payment_title', false),
                    'cash_title' => get_option('kadio_deposit_settings_payment_cash_title', false),
                    'deposit_title' => get_option('kadio_deposit_settings_payment_deposit_title', false),
                );
                foreach ($cart as $key => $cart_item) {
                    $product_id = $cart_item['data']->get_id();

                    $is_deposit_illegible = get_post_meta($product_id, "kadio_deposit_add_product_custom_fields_deposit", true);
                    //$product->update_meta_data("kadio_deposit_add_product_custom_fields_deposit", $is_deposit_illegible);
                    $data['is_deposit_illegible_' . $product_id] = $is_deposit_illegible;
                    $this->save_deposit_session_is_product_eligible_data($product_id, $is_deposit_illegible);
                }
                $this->save_deposit_session_data($data, $product_id, $is_deposit_illegible);
            } else {
                if (empty($data['amount'])) {
                    $data['amount'] = get_option('kadio_deposit_settings_amount');
                } elseif (empty($data['kadio_deposit_for_product_or_total'])) {
                    $data['kadio_deposit_for_product_or_total'] = get_option('kadio_deposit_settings_select_deposit_for_product_or_total');
                } elseif (empty($data['kadio_deposit_apply_on_shipping'])) {
                    $data['kadio_deposit_apply_on_shipping'] = get_option('kadio_deposit_apply_on_shipping');
                } elseif (empty($data['payment_title'])) {
                    $data['payment_title'] = get_option('kadio_deposit_settings_payment_title', false);
                } elseif (empty($data['cash_title'])) {
                    $data['cash_title'] = get_option('kadio_deposit_settings_payment_cash_title', false);
                } elseif (empty($data['deposit_title'])) {
                    $data['deposit_title'] = get_option('kadio_deposit_settings_payment_deposit_title', false);
                }

                foreach ($cart as $key => $cart_item) {
                    $product_id = $cart_item['data']->get_id();
                    $is_deposit_illegible = get_post_meta($product_id, "kadio_deposit_add_product_custom_fields_deposit", true);
                    $data['is_deposit_illegible_' . $product_id] = $is_deposit_illegible;
                    $this->save_deposit_session_is_product_eligible_data($product_id, $is_deposit_illegible);
                    //$product->update_meta_data("kadio_deposit_add_product_custom_fields_deposit", $is_deposit_illegible);
                }
                $this->save_deposit_session_data($data, $product_id, $is_deposit_illegible);
            }
        }

        /**
         * Save deposit session data
         *
         * @param $data
         * @param $product_id
         * @param $is_product_deposit_eligible
         * @return void
         */
        protected function save_deposit_session_data($data)
        {
            WC()->session->set('kadio_deposit_session_cart_items', $data);
        }

        protected function save_deposit_session_is_product_eligible_data($product_id, $is_product_deposit_eligible)
        {

            WC()->session->set('is_deposit_illegible_' . $product_id, array(
                'is_illegible' => $is_product_deposit_eligible,
            ));
        }

        public function get_deposit_by_cart()
        {
            $session = WC()->session;
            if ($session) {
                $kadio_deposit_session_cart_items = $session->get('kadio_deposit_session_cart_items');
                if (!is_null($kadio_deposit_session_cart_items)) {
                    return doubleval($kadio_deposit_session_cart_items['deposit']);
                }
            }
            return doubleval(get_option('kadio_deposit_settings_amount') ?? 0);
        }

        public function get_deposit_by_order($order_id)
        {
            if (!empty($order_id)) {
                return doubleval(get_post_meta($order_id, 'kadio_deposit', true));
            }
            return doubleval(get_option('kadio_deposit_settings_amount') ?? 0);
        }

        public function kadio_var_dump($array, $name = 'var')
        {
            highlight_string("<?php\n\$$name =\n" . var_export($array, true) . ";\n?>");
        }

        public function get_coupon_discount_amount($order_id = null, $cart = null)
        {
            $coupon_total_amount = 0;
            if ($order_id) {
                $order = wc_get_order($order_id);
                $order_items = $order->get_items();
                $sub_total = number_format($order->get_subtotal(), 2);
                $coupons = $order->get_coupon_codes();
                $coupon_total_amount = 0;
                foreach ($coupons as $key => $coupon) {
                    $coupon = new WC_Coupon($coupon);
                    $coupon_amount = doubleval($coupon->get_amount());
                    $coupon_type = $coupon->get_discount_type();
                    if ('percent' === $coupon_type) {
                        $coupon_total_amount += floor($sub_total * $coupon_amount * 0.01);
                    } else if ('fixed_cart' === $coupon_type) {
                        $coupon_total_amount += $coupon_amount;
                    } else if ('fixed_product' === $coupon_type) {
                        foreach ($order_items as $key => $order_item) {

                            $price_unit = doubleval($order_item->get_total());
                            $price = $price_unit;

                            $price = $price_unit;
                            if ($coupon_amount > $price) {
                                $coupon_total_amount += $price;
                            } else {
                                $coupon_total_amount += $coupon_amount;
                            }
                        }
                    }
                }
            } else {
                $coupons = WC()->cart->get_coupons();
                $sub_total = doubleval(preg_replace("/[^0-9!,.]/", "", WC()->cart->get_cart_subtotal()));

                $coupon_total_amount = 0;

                foreach ($coupons as $key => $coupon) {
                    $coupon_amount = doubleval($coupon->get_amount());
                    $coupon_type = $coupon->get_discount_type();
                    if ('percent' === $coupon_type) {
                        $coupon_total_amount += floor($sub_total * $coupon_amount * 0.01);
                        
                    } else if ('fixed_cart' === $coupon_type) {
                        $coupon_total_amount += $coupon_amount;
                    } else if ('fixed_product' === $coupon_type) {
                        foreach ($cart as $key => $cart_item) {
                            $product = wc_get_product($cart_item['data']->get_id());

                            $price_unit = $product->get_price();
                            $quantity = $cart_item['quantity'];
                            $price = $price_unit * $quantity;
                            if ($coupon_amount > $price) {
                                $coupon_total_amount += $price;
                            } else {
                                $coupon_total_amount += $coupon_amount;
                            }
                        }
                    }
                }
            }

            return $coupon_total_amount;
        }
    }
}
