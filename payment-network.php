<?php
/*
Plugin Name: PaymentNetwork
Description: Provides the PaymentNetwork Payment Gateway for WooCommerce
Version: 1.5.0
*/

/**
 * Initialise WC Payment Network Gateway
 **/
add_action('plugins_loaded', 'init_wc_payment_network', 0);


/**
 * Initialise WC PaymentNetwork Gateway
 **/
if (function_exists('setup_module_database_tables')) {
	register_activation_hook(__FILE__, 'setup_module_database_tables');
}

/**
 * Delete PaymentNetwork Gateway
 **/
if (function_exists('delete_plugin_database_table')) {
	register_uninstall_hook(__FILE__, 'delete_plugin_database_table');
}

function init_wc_payment_network()
{

	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	// Load classes required.
	include('includes/p3/php-sdk/src/Gateway.php');
	include('includes/p3/php-sdk/src/Client.php');
	include('includes/p3/php-sdk/src/AmountHelper.php');
	include('includes/p3/php-sdk/src/CreditCard.php');

	add_filter('plugin_action_links', 'add_wc_payment_network_action_plugin', 10, 5);

	add_filter('woocommerce_payment_gateways', 'add_payment_network_payment_gateway');

	include('includes/class-wc-payment-network.php');
}

function add_wc_payment_network_action_plugin($actions, $plugin_file)
{
	static $plugin;

	if (!isset($plugin)) {
		$plugin = plugin_basename(__FILE__);
	}

	if ($plugin == $plugin_file) {
		$configs = include(dirname(__FILE__) . '/config.php');

		$section = str_replace(' ', '', strtolower($configs['default']['gateway_title']));

		$actions = array_merge(array('settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $section) . '">' . __('Settings', 'General') . '</a>'), $actions);
	}

	return $actions;
}

function add_payment_network_payment_gateway($methods)
{
	$methods[] = 'WC_Payment_Network';
	return $methods;
}


function setup_module_database_tables()
{
	$module_prefix = 'payment_network_';
	global $wpdb;
	global $jal_db_version;

	//Wallet table name.
	$wallet_table_name = $wpdb->prefix . 'woocommerce_' . $module_prefix . 'wallets';

	if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wallet_table_name)) ===  null) {
		$table_name = $wallet_table_name;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(9) NOT NULL AUTO_INCREMENT,
			merchants_id INT NOT NULL,
			users_id BIGINT NOT NULL,
			wallets_id BIGINT NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('jal_db_version', $jal_db_version);
	}
}

function delete_plugin_database_table()
{
	$module_prefix = 'payment_network_';
	global $wpdb;
	$wpdb->show_errors();
	$table_name = $wpdb->prefix . 'woocommerce_' . $module_prefix . 'wallets';
	$sql = "DROP TABLE IF EXISTS $table_name";
	$wpdb->query($sql);
	//delete_option("my_plugin_db_version");
	//error_log('Logging SQL table drop');
}

function pn_enqueue_frontend_scripts($hook) {
    $configs = include(dirname(__FILE__) . '/config.php');

    $kount_merchant_id = $configs['kount']['id'];
    $environment = $configs['kount']['environment'];
    $session_id = substr(uniqid().uniqid().uniqid(),0,32);

    if (!is_checkout()) {
        return;
    }

    if (function_exists('WC') && WC()->session) {
        WC()->session->set('pn_session_id', $session_id);
    }

    wp_enqueue_script(
        'pn-checkout-js',
        plugin_dir_url(__FILE__) . 'assets/js/checkout.js',
        array('jquery'),
        '1.0.0',
        true
    );

    wp_localize_script('pn-checkout-js', 'pnVars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('pn_nonce'),
        'session_id' => $session_id,
        'kount_merchant_id'  => $kount_merchant_id,
        'environment' => $environment,
    ));

    wp_enqueue_script(
        'kount-sdk',
        plugin_dir_url(__FILE__) . 'assets/js/kount-web-client-sdk.js',
        array(),
        '1.0.0',
        true
    );
}

add_action('wp_enqueue_scripts', 'pn_enqueue_frontend_scripts');