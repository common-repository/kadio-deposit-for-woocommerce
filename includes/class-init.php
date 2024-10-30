<?php

if (!class_exists('Kadio_Init')) {
    class Kadio_Init
    {
        /** Minimum WordPress version required by the plugin */
        const MINIMUM_WP_VERSION = "5.4.0";

        /** Minimum WooCommerce version required by the plugin */
        const MINIMUM_WC_VERSION = "5.7.0";

        /** Minimum PHP version required by the plugin */
        const MINIMUM_PHP_VERSION = "7.3.0";

        /** The plugin name, for displaying notices */
        const PLUGIN_NAME = "Kadio Deposit for Woocommerce";

        /**
         * @var array the admin notices
         */
        private array $notices = [];

        /**
         * Constructor
         */
        public function __construct()
        {
            register_activation_hook(__FILE__, [$this, 'kadio_deposit_activation_check']);

            add_action('admin_init', [$this, 'kadio_deposit_check_environment']);
            add_action('admin_init', [$this, 'kadio_deposit_add_plugin_notices']);

            add_action('admin_notices', [$this, 'kadio_deposit_admin_notices'], 15);

            add_action('wp_enqueue_scripts', [$this, 'kadio_deposit_script_enqueue']);
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
         * Method to add errors notices messages
         */
        public function kadio_deposit_admin_notices()
        {
            foreach ((array)$this->notices as $notice_key => $notice) {

                ?>
                <div class="<?php echo esc_attr($notice['class']); ?>">
                    <p><?php echo wp_kses($notice['message'], array('a' => array('href' => array()))); ?></p>
                </div>
                <?php
            }
        }

        /**
         * Method to add admin notices messages
         */
        public function kadio_deposit_add_plugin_notices()
        {
            if (!$this->kadio_deposit_is_wp_compatible()) {

                $this->kadio_deposit_add_admin_notice(
                    'update_wordpress',
                    'error',
                    sprintf(
                        __('%s requires WordPress version %s or higher. Please %s update WordPress %s', 'kadio-deposit-for-woocommerce'),
                        '<strong>' . self::PLUGIN_NAME . '</strong>',
                        self::MINIMUM_WP_VERSION,
                        '<a href="' . esc_url(admin_url('update-core.php')) . '">',
                        '</a>'
                    )
                );
            }

            if (!$this->kadio_deposit_is_wc_compatible()) {

                $this->add_admin_notice(
                    'update_woocommerce',
                    'error',
                    sprintf(
                        __('%1$s requires WooCommerce version %2$s or higher. Please %3$s update WooCommerce%4$s to the latest version, or %5$s download the minimum required version %6$s', 'kadio-deposit-for-woocommerce')
                        ,
                        '<strong>' . self::PLUGIN_NAME . '</strong>',
                        self::MINIMUM_WC_VERSION,
                        '<a href="' . esc_url(admin_url('update-core.php')) . '">',
                        '</a>',
                        '<a href="' . esc_url('https://downloads.wordpress.org/plugin/woocommerce.' . self::MINIMUM_WC_VERSION . '.zip') . '">',
                        '</a>'
                    )
                );
            }
        }

        /**
         * Check env and plugin state and disable plugin if is active and environment is not compatible
         */
        public function kadio_deposit_check_environment()
        {
            if (!$this->kadio_deposit_is_environment_compatible() && is_plugin_active(plugin_basename(__FILE__))) {
                $this->kadio_deposit_deactivate_plugin();

                $this->kadio_deposit_add_admin_notice(
                    "bad_environment",
                    'error',
                    __(self::PLUGIN_NAME . ' has been deactivated. ' . $this->kadio_deposit_get_environment_message(), 'kadio-deposit-for-woocommerce')
                );
            }

            if (!defined('WC_VERSION')) {
                $this->kadio_deposit_deactivate_plugin();
                $this->kadio_deposit_add_admin_notice(
                    "bad_environment",
                    'error',
                    __(self::PLUGIN_NAME . ' has been deactivated. Woocommerce is not installed', 'kadio-deposit-for-woocommerce')
                );
            }
        }

        /**
         * Check if plugin is compatible with WordPress
         * @return bool|int
         */
        private function kadio_deposit_is_wp_compatible()
        {
            if (!self::MINIMUM_WP_VERSION) {
                return true;
            }

            return version_compare(get_bloginfo('version'), self::MINIMUM_WP_VERSION, '>=');
        }


        public function kadio_deposit_activation_check()
        {
            if (!$this->kadio_deposit_is_environment_compatible()) {

                $this->kadio_deposit_deactivate_plugin();

                wp_die(__(self::PLUGIN_NAME . ' could not be activated. ' . $this->kadio_deposit_get_environment_message(), 'kadio-deposit-for-woocommerce'));
            }
        }

        /**
         * Method to check all environment compatibility
         * @return bool
         */
        public function kadio_deposit_is_environment_compatible(): bool
        {
            return version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=') &&
                version_compare($GLOBALS['wp_version'], self::MINIMUM_WP_VERSION, '>=') &&
                defined('WC_VERSION') && version_compare(WC_VERSION, self::MINIMUM_WC_VERSION, '>=');
        }

        /**
         * Running method when plugin is disabled
         */
        private function kadio_deposit_deactivate_plugin()
        {
            deactivate_plugins(plugin_basename(__FILE__));
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }

        /**
         * Get message when php version is incompatible with environment
         * @return string
         */
        private function kadio_deposit_get_environment_message(): string
        {
            return sprintf(
                __('The minimum PHP version required for this plugin is %1$s. You are running %2$s.\n The minimum Woocommerce version required for this plugin is %3$s.\n The minimum WordPress version required for this plugin is %4$s.', 'kadio-deposit-for-woocommerce'),
                self::MINIMUM_PHP_VERSION,
                PHP_VERSION,
                self::MINIMUM_WC_VERSION,
                self::MINIMUM_WP_VERSION
            );
        }

        /**
         * Check if plugin is compatible with WordPress
         * @return bool|int
         */
        private function kadio_deposit_plugins_compatible()
        {
            return $this->kadio_deposit_is_wp_compatible();
        }

        /**
         * Check WooCommerce version compatibility
         * @return bool
         */
        private function kadio_deposit_is_wc_compatible(): bool
        {
            if (!self::MINIMUM_WC_VERSION) {
                return true;
            }

            return defined('WC_VERSION') && version_compare(WC_VERSION, self::MINIMUM_WC_VERSION, '>=');
        }

        private function kadio_deposit_add_admin_notice($slug, $class, $message)
        {
            $this->notices[$slug] = array(
                'class' => $class,
                'message' => $message,
            );
        }

        public function kadio_deposit_script_enqueue()
        {
            // please create also an empty JS file in your theme directory and include it too
            wp_enqueue_script('kadio-deposit-for-woocommerce', plugin_dir_url(dirname(__FILE__)) . 'assets/js/script.js', array('jquery'));
            wp_enqueue_style('kadio_sendcloud_listing_tailwind', plugin_dir_url(dirname(__FILE__)) . 'assets/css/style.css', array(), '3.3.2');

            wp_localize_script(
                'kadio-deposit-for-woocommerce',
                'kadio_deposit_for_woocommerce',
                array(
                    'ajaxurl'      => admin_url('admin-ajax.php'),
                    'ajaxnonce'   => wp_create_nonce('kadio_deposit_ajax_validation') // <--- Security!
                )
            );
        }
    }
}
