<?php

if (!class_exists('Kadio_Deposit')) {
    class Kadio_Deposit extends Kadio_Base_Controller
    {
        /**
         * Constructor
         */
        public function __construct()
        {
            add_action('woocommerce_review_order_before_payment', [$this, 'kadio_deposit_add_custom_fields']);
            add_action('woocommerce_checkout_update_order_meta', [$this, 'kadio_deposit_save_custom_fields']);
            add_action('woocommerce_admin_order_totals_after_total', [$this, 'kadio_deposit_admin_order'], 10);
            add_action('woocommerce_before_pay_action', [$this, 'kadio_deposit_initiate_order'], 10, 1);
            add_action('woocommerce_review_order_after_order_total', [$this, 'kadio_deposit_display_amount_to_pay']);
            add_action('woocommerce_pay_order_before_submit', [$this, 'kadio_deposit_display_payment_message']);
            add_action('woocommerce_order_details_after_order_table', [$this, 'kadio_deposit_display_data_in_order_details']);

            add_action('woocommerce_email_order_meta', [$this, 'kadio_deposit_display_data_in_email'], 10, 4);

            add_action('woocommerce_order_status_pending_to_processing', [$this, 'kadio_deposit_woo_order_status_change_custom'], 10, 3);
            // add new status transition
            add_action('woocommerce_order_status_partial-payment_to_processing', [$this, 'kdc_woocommerce_order_status_changed'], 10, 3);

            add_action('woocommerce_pay_order_before_submit', [$this, 'kadio_deposit_amount_to_be_paid'], 10, 2);

            add_action('woocommerce_applied_coupon', [$this, 'kadio_deposit_applied_coupon']);
            add_action('woocommerce_removed_coupon', [$this, 'kadio_deposit_removed_coupon']);

            //add_filter('woocommerce_email_heading_customer_completed_order', [$this, 'custom_heading_customer_completed_order'], 10, 2);

            // // Ajax request when we apply coupon
            // add_action('wp_ajax_', [$this, 'kadioDepositAJAX_callback']);
            // add_action('wp_ajax_no_priv_kadioDepositAJAX_callback', [$this, 'kadioDepositAJAX_callback']);

            add_filter( 'woocommerce_email_classes', [$this, 'kdc_add_custom_email'] );
        }

        public function kdc_add_custom_email( $email_classes )
        {
            // Include the custom email class
            //require_once KDC_PLUGIN_DIR_PATH . 'includes/class-wc-custom-email.php';
            // Add the custom email class to the list of WooCommerce email classes
            require_once KDC_WD_BASE_PATH . "/includes/emails/class-kdc-email-customer-remaining-payment.php";
            $email_classes['Kdc_Remaining_Payment_Completed'] = new Kdc_Remaining_Payment_Completed();
            return $email_classes;
        }

        public function custom_heading_customer_completed_order($heading, $order)
        {
            // Vérifiez le statut de la commande
            if ($order->has_status('processing')) {
                $heading = __('Your order is being processed', 'kadio-deposit-for-woocommerce');
            }
            return $heading;
        }

        public function kdc_woocommerce_order_status_changed($order_id, $order)
        {
            error_log("SEND CUSTOM EMAIL TO USERS");
            //Envoyez un email à l'utilisateur
            $this->custom_order_status_changed_email($order_id, $order);

            if (!$order_id) return;
            //$order = wc_get_order($order_id);

            list($deposit, $deposit_paid) = $this->check_if_is_deposit_paid_or_remaining_paid($order_id);
            $kadio_deposit_from_partial_payment_to_process_done = get_post_meta($order_id, "kadio_deposit_from_partial_payment_to_process_done", true);

            if ($this->is_payplug_payment($order) && !$kadio_deposit_from_partial_payment_to_process_done && $deposit != '1') {
                $this->set_order_as_partial_paid($order);
                update_post_meta($order_id, "kadio_deposit_from_partial_payment_to_process_done", true);
                return;
            }

            //$deposit = get_post_meta($order_id, 'kadio_deposit_new', true);
            //$deposit_paid = boolval(get_post_meta($order_id, 'kadio_deposit_paid', true));

            if ($this->is_payplug_payment($order)) {
                $createdDate = $order->get_date_created();
                if (!is_null($createdDate)) {
                    $createdDateTime = new DateTime();
                    $createdDateTime->setTimestamp($createdDate->getTimestamp());
                    // Add 10 Secondes
                    //$createdDateTime->add(new DateInterval('PT10S'));
                    // Add 60 Secondes
                    $createdDateTime->add(new DateInterval('PT60S'));
                    // Add 1 minute
                    //$createdDateTime->add(new DateInterval('PT1M'));

                    $current = new DateTime();
                    $current->setTimestamp(time());

                    if (($deposit_paid && $deposit != '1') && ($current > $createdDateTime)) {
                        update_post_meta($order_id, 'kadio_deposit_new', 1);
                    } else {
                        $this->set_order_as_partial_paid($order);
                    }
                }
            } else {
                if ($deposit_paid && $deposit != '1') {
                    update_post_meta($order_id, 'kadio_deposit_new', 1);
                    //update_post_meta($order_id, 'kadio_deposit', 1);
                }
            }
        }
        public function kadio_deposit_amount_to_be_paid()
        {
            echo show_deposit_remaining_amount_view();
        }

        // Add some fields on checkout page
        public function kadio_deposit_add_custom_fields($checkout = null)
        {

            // $amount = get_option('kadio_deposit_settings_amount', false);
            $kadio_deposit_session_cart_items = WC()->session->get('kadio_deposit_session_cart_items');
            $amount = $kadio_deposit_session_cart_items['deposit'];
            // les coupous
            $kadio_deposit_when_using_coupon = $kadio_deposit_session_cart_items['kadio_deposit_when_using_coupon'];

            $payment_title = $kadio_deposit_session_cart_items['payment_title'];
            $cash_title = $kadio_deposit_session_cart_items['cash_title'];
            $deposit_title = $kadio_deposit_session_cart_items['deposit_title'];

            // $payment_title = get_option('kadio_deposit_settings_payment_title', false);
            // $cash_title = get_option('kadio_deposit_settings_payment_cash_title', false);
            // $deposit_title = get_option('kadio_deposit_settings_payment_deposit_title', false);

            if (empty($amount)) {

                // $amount = get_option('kadio_deposit_settings_amount', '');
                // $data = [
                //     'deposit' => $amount,
                //     'payment_title' => $payment_title,
                //     'deposit_title' => $deposit_title,
                //     'cash_title' => $cash_title
                // ];
                $kadio_deposit_session_cart_items['deposit'] = $kadio_deposit_session_cart_items['deposit'];
                $kadio_deposit_session_cart_items['payment_title'] = $payment_title;
                $kadio_deposit_session_cart_items['cash_title'] = $cash_title;


                $this->refresh_session_data($kadio_deposit_session_cart_items);
            }

            if ($amount == 0 || empty($amount)) return;

            if (empty($payment_title)) {
                $payment_title = __("Payment facilities", 'kadio-deposit-for-woocommerce');
            } else {
                $payment_title = __($payment_title, 'kadio-deposit-for-woocommerce');
            }

            if (empty($cash_title)) {
                $cash_title = __("Cash", 'kadio-deposit-for-woocommerce');
            } else {
                $cash_title = __($cash_title, 'kadio-deposit-for-woocommerce');
            }

            if (empty($deposit_title)) {
                $deposit_title = sprintf(__("Deposit of %s", 'kadio-deposit-for-woocommerce'), $amount . "%");
            } else {
                $deposit_title = sprintf(__($deposit_title, 'kadio-deposit-for-woocommerce'), $amount . "%");
            }


            $amount = intval($amount) * 0.01;
            wp_kses_post(woocommerce_form_field('kadio_deposit', array(

                'default' => 1,

                'type' => 'radio', // text, textarea, select, radio, checkbox, password, about custom validation a little later

                'required' => true, // actually this parameter just adds "*" to the field

                'class' => ['kadio-deposit'], // array only, read more about classes and styling in the previous step

                'label' => $payment_title,

                //        'label_class'   => 'ans-label', // sometimes you need to customize labels, both string and arrays are supported

                'options' => array( // options for


                    '1' => $cash_title, // empty values means that field is not selected

                    '' . $amount => $deposit_title, // 'value'=>'Name'

                )

            ), '1'));
        }

        /**
         * Update order with deposit data
         *
         * @param $order_id
         */
        public function kadio_deposit_save_custom_fields($order_id)
        {
            if (isset($_POST['kadio_deposit']) && !empty($_POST['kadio_deposit'])) {
                $deposit = sanitize_text_field($_POST['kadio_deposit']);
                if ($deposit != '1') {
                    //update_post_meta($order_id, 'kdc_is_paid_by_deposit', true);
                    update_post_meta($order_id, 'kdc_without_deposit', false);
                } else {
                    //update_post_meta($order_id, 'kdc_is_paid_by_deposit', false);

                    // When payment is done without deposit
                    update_post_meta($order_id, 'kdc_without_deposit', true);
                }
                update_post_meta($order_id, 'kadio_deposit', $deposit);
                update_post_meta($order_id, 'kadio_deposit_paid', false);
                // Register paid deposit amount
                $order = wc_get_order($order_id);

                // On enregistre les informations
                update_post_meta($order_id, 'kadio_deposit_new', $deposit);

                $kadio_deposit_for_product_or_total = $this->get_deposit_level();
                update_post_meta($order_id, 'kadio_deposit_for_product_or_total', $kadio_deposit_for_product_or_total);

                $kadio_deposit_apply_on_shipping = $this->get_deposit_shipping_method_application();
                update_post_meta($order_id, 'kadio_deposit_apply_on_shipping', $kadio_deposit_apply_on_shipping);

                $cart_items = $order->get_items();
                foreach ($cart_items as $key => $item) {
                    if ($kadio_deposit_for_product_or_total == "product") {
                        $product = $item->get_product();
                        // $is_deposit_illegible = $product->get_meta('kadio_deposit_add_product_custom_fields_deposit');
                        $is_deposit_illegible = $this->is_product_eligible($product->get_id());
                        if ($is_deposit_illegible) {
                            wc_update_order_item_meta($key, 'kadio_deposit_illegible', "yes");
                        }
                    }
                }


                $deposit_to_pay = $this->kadio_deposit_to_pay_by_order($order, $deposit);

                update_post_meta($order_id, 'kadio_deposit_paid_amount', $deposit_to_pay);
            }
        }

        public function kadio_deposit_admin_order($order_id)
        {
            $deposit = get_post_meta($order_id, 'kadio_deposit_new', true);
            $order = new WC_Order($order_id);
            $deposit_to_pay = $this->kadio_deposit_to_pay_by_order($order, (doubleval($deposit)) * 0.01);
            $data = [
                'deposit' => $deposit,
                'deposit_to_pay' => $deposit_to_pay,
                'order' => $order
            ];
            echo show_admin_order_deposit_data($data);
        }

        public function kadio_deposit_initiate_order($order)
        {
            if (!$order) return;

            //    $order = wc_get_order( $order_id );

            $deposit = $order->get_meta('kadio_deposit_new', true);
            $deposit_paid = $order->get_meta('kadio_deposit_paid', true);

            if ($deposit_paid && $deposit != '1') {
                $order->update_meta_data('_stripe_intent_id', '');
                $order->update_meta_data('_stripe_setup_intent', '');
            }
        }

        public function kadio_deposit_display_amount_to_pay($option = null)
        {
            //$deposit = get_option('kadio_deposit_settings_amount', false);
            $deposit = $this->get_deposit_by_cart();
            $deposit_to_pay = $this->kadio_deposit_to_pay_by_cart(WC(), $deposit * 0.01);
            $title = $this->kadio_deposit_account_to_pay_title($option);

            $data = [
                'deposit' => $deposit,
                'deposit_to_pay' => $deposit_to_pay,
                'deposit_title' => $title
            ];
            echo show_deposit_on_order_review($data);
        }

        public function kadio_deposit_display_payment_message()
        {
            echo show_deposit_remaining_message_view();
        }

        public function kadio_deposit_display_data_in_order_details($order)
        {
            if (!$order) return;

            //    $order = wc_get_order( $order_id );

            $deposit = $order->get_meta('kadio_deposit_new', true);

            $show_details = get_option('kadio_deposit_settings_show_details', false);

            // error_log($show_details);

            if ($show_details === 'yes') :;
                //$order->update_status('pending');
                $this->kadio_deposit_data_to_display($deposit, $order);

            endif;
        }

        public function kadio_deposit_display_data_in_email($order, $sent_to_admin, $text, $email)
        {
            $deposit = $order->get_meta('kadio_deposit_new', true);

            $deposit_paid = $order->get_meta('kadio_deposit_paid', true);
            if ($deposit_paid == "1") {
                $deposit = "1";
            }
            $show_details = get_option('kadio_deposit_settings_show_email', false);
            if ($show_details === 'yes' /* && $deposit != "1" */) {
                $this->kadio_deposit_data_to_display($deposit, $order);
            }
        }

        /**
         * @param $order_id
         * @param $order
         */
        public function kadio_deposit_woo_order_status_change_custom($order_id, $order)
        {
            if (!$order_id) return;
            //$order = wc_get_order($order_id);

            list($deposit, $deposit_paid) = $this->check_if_is_deposit_paid_or_remaining_paid($order_id);
            $deposit_paid = boolval($deposit_paid);
            if (!$deposit_paid && $deposit != '1') {
                $this->set_order_as_partial_paid($order);
                update_post_meta($order->get_id(), 'kadio_deposit_from_partial_payment_to_process_done', false);
            }
            $this->save_deposit_session_data([]);
        }


        /**
         * kadio_deposit_data_to_display
         * Print data thank you page
         * @param mixed $deposit
         * @param mixed $order
         * @return void
         */
        private function kadio_deposit_data_to_display($deposit, $order)
        {
            $data = [
                'deposit' => $deposit,
                'order' => $order
            ];
            if ($deposit != '1') {
                $data['deposit_to_pay'] = $this->kadio_deposit_to_pay_by_order($order, $deposit);
            }
            echo show_deposit_display_data($data);
        }

        /**
         * @param $order_id
         * @return array
         */
        private function check_if_is_deposit_paid_or_remaining_paid($order_id): array
        {
            $deposit = get_post_meta($order_id, 'kadio_deposit_new', true);
            $deposit_paid = boolval(get_post_meta($order_id, 'kadio_deposit_paid', true));

            /*if (($deposit_paid)) {
                $deposit_paid = false;
            }*/

            return array($deposit, $deposit_paid);
        }

        private function set_order_as_partial_paid($order)
        {
            // $order->update_status('pending');
            // Update payment status when we are in partial payment
            //$order->update_status('partial-payment');
            $order->set_status('partial-payment');
            $order->save();

            update_post_meta($order->get_id(), 'kadio_deposit_paid', true);

            $order->update_meta_data('_stripe_intent_id', '');

            $order->update_meta_data('_stripe_setup_intent', '');
        }

        private function is_payplug_payment($order)
        {
            return strtolower($order->get_payment_method()) === "payplug";
        }

        public function kadio_deposit_applied_coupon()
        {


            $session = WC()->session;
            $kadio_deposit_session_cart_items = $session->get('kadio_deposit_session_cart_items');

            // Get deposit statut when coupon is used
            $kadio_deposit_when_using_coupon = $kadio_deposit_session_cart_items['kadio_deposit_when_using_coupon'];

            // If yes the deposit will be disabled when coupon is used. If no, the deposit will be applied automatically on cart total when coupon is used.

            if ($kadio_deposit_when_using_coupon == 'yes') {
                // set disabled deposit
                $kadio_deposit_session_cart_items['deposit'] = null;
                $session->set('kadio_deposit_session_cart_items', $kadio_deposit_session_cart_items);
                echo '<script type="text/JavaScript"> 
                    jQuery(function($){
                        $("#kadio_deposit_1").prop("checked", true);
                        $("#kadio_deposit_field").hide();
                    });
                    </script>';

                //     var KadioDeposit = document.getElementById("kadio_deposit_field");
                // console.log(KadioDeposit);
                // kadioDeposit.style.display = "none";
            } else {
                // then apply deposit on cart
                $kadio_deposit_session_cart_items['kadio_deposit_for_product_or_total'] = "total";
                $session->set('kadio_deposit_session_cart_items', $kadio_deposit_session_cart_items);
                echo '<script type="text/JavaScript"> 
                    jQuery(function($){
                        $("#kadio_deposit_1").prop("checked", true);
                        $("#kadio_deposit_field").show();
                    });
                    </script>';
            }
        }

        public function kadio_deposit_removed_coupon()
        {

            // get coupon amount
            $coupon_amount = $this->get_coupon_discount_amount(null, WC()->cart);
            if ($coupon_amount <= 0) {
                $session = WC()->session;
                $kadio_deposit_session_cart_items = $session->get('kadio_deposit_session_cart_items');
                // then apply deposit on cart
                $kadio_deposit_session_cart_items['kadio_deposit_for_product_or_total'] = $kadio_deposit_session_cart_items['kadio_deposit_for_product_or_total_temp'];
                $kadio_deposit_session_cart_items['deposit'] = $kadio_deposit_session_cart_items['deposit_temp'];
                $session->set('kadio_deposit_session_cart_items', $kadio_deposit_session_cart_items);
                $kadio_deposit_session_cart_items = $session->get('kadio_deposit_session_cart_items');
                echo '<script type="text/JavaScript"> 
                        jQuery(function($){
                            $("#kadio_deposit_1").prop("checked", true);
    
                            $(".order-deposit").show();
                            $("#kadio_deposit_field").show();
                        });
                        </script>';
            }
            // reset values

        }

        public function custom_order_status_changed_email($order_id, $order = false)
        {
            // Vérifier si le nouveau statut est celui pour lequel vous souhaitez envoyer un email
            $mailer = WC()->mailer();
            $mails = $mailer->get_emails();
            error_log("ALL WC EMAILS = " . var_export($mails, true));
            if (!empty($mails)) {
                // Utiliser le modèle d'email par défaut de WooCommerce pour les commandes complétées
                // $mails['WC_Email_Customer_Completed_Order']->trigger($order_id);
                // $mails['WC_Email_Customer_Completed_Order'] = new WC_Email_Custom_Order_Status();
                //$mails['WC_Email_Customer_Completed_Order']->trigger($order_id);
                $mails['Kdc_Remaining_Payment_Completed']->trigger($order_id, $order);
            }
        }
        // function kadioDepositAJAX_callback()
        // {
        //     check_ajax_referer('kadio_deposit_ajax_validation', 'security');
        //     $kadio_deposit_session_cart_items = WC()->session->get('kadio_deposit_session_cart_items');
        //     $deposit = $kadio_deposit_session_cart_items['deposit'];
        //     wp_send_json_success($deposit);
        //     wp_die();
        // }
    }
}
