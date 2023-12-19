<?php
/**
 * Plugin Name: Mirakl Woocommerce Connector
 *  Description: Connect Mirakl with WooCommerce.
 * Version: 1.0.0
 * Author: Mohamed Ayoub Jabane & Abdellatif Bouziane
 * Tested up to: 8.4
 * WC requires at least: 3.0
 * WC tested up to: 8.4
 * Requires at least: 5.6
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


// Define ABSPATH if not already defined
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

function plugin_mirakl_connector_activation() {
    global $wpdb;

    // Create the Mirakl settings table
    $table_mirakl_settings = $wpdb->prefix . 'mirakl_settings';
    $table_mirakl_sku_mappings = $wpdb->prefix . 'mirakl_sku_mappings';

    $charset_collate = $wpdb->get_charset_collate();

    // SQL for creating the Mirakl settings table
    $sql_mirakl_settings = "CREATE TABLE $table_mirakl_settings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        mirakl_api_endpoint varchar(255) NOT NULL,
        mirakl_api_key varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // SQL for creating the SKU mappings table
    $sql_mirakl_sku_mappings = "CREATE TABLE $table_mirakl_sku_mappings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        external_sku varchar(255) NOT NULL,
        woocommerce_sku varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Execute SQL queries to create tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_mirakl_settings);
    dbDelta($sql_mirakl_sku_mappings);

    // Create additional tables if needed
    // create_general_settings_table();
}
// Register activation hook to create a custom table
register_activation_hook(__FILE__, 'plugin_mirakl_connector_activation');
// Add a button to trigger the Mirakl order retrieval
add_action('admin_menu', 'mirakl_integration_admin_menu');

function mirakl_integration_admin_menu() {
    add_menu_page(
        'Mirakl Connector',
        'Mirakl Connector',
        'manage_options',
        'mirakl-integration',
        'mirakl_integration_settings_page',
        'dashicons-block-default',
        6
    );

    // Add submenu page for Order Export Log
    add_submenu_page('mirakl-integration', 'Channels Orders Import', 'Channels Orders Import', 'manage_options', 'mirakl-integration', 'mirakl_integration_settings_page');

    // Add submenu page for SKU Mapping

    add_submenu_page('mirakl-integration', 'SKU Mapping', 'SKU Mapping', 'manage_options', 'sku-mapping', 'mirakl_connector_sku_mapping_page');
    // Add submenu page for Stock Management
    add_submenu_page('mirakl-integration', 'Stock Management', 'Stock Management', 'manage_options', 'stock-management', 'mirakl_connector_stock_management_page');

    // Add submenu page for API Credentials
    add_submenu_page('mirakl-integration', 'API Credentials', 'API Credentials', 'manage_options', 'api-credentials', 'mirakl_connector_api_credentials_page');

    //add_submenu_page('mirakl-integration', 'Import Orders Settings', 'Import Time Settings', 'manage_options', 'custom-cron-settings', 'mirakl_connector_custom_cron_settings_page');
}

function mirakl_integration_settings_page() {
    ?>
    <div class="mirakl-settings-wrapper">
        <div class="wrap">
            <h2>Channels Orders Import</h2>

            <!-- Section 1: Import, Export, and Manual Sync of Orders -->
            <form method="post" action="">
                <?php
                if (isset($_POST['get_orders'])) {
                    // Retrieve settings from the form and get orders
                    $credentials = mirakl_connector_get_api_credentials();
                    $base_api = $credentials['mirakl_api_endpoint'];
                    $api_key = $credentials['mirakl_api_key'];
                    mirakl_connector_get_orders_from_api($base_api, $api_key); // Pass the API credentials here
                }
                ?>
                <table class="form-table">
                    <tr>
                        <th></th>
                        <td><input type="submit" class="button-primary" name="get_orders" value="Get Mirakl Orders" /></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
    <?php
}
function mirakl_connector_sku_mapping_page() {
    global $wpdb;

    // Function to get WooCommerce SKUs
    function get_woocommerce_skus() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
        );

        $products = get_posts($args);
        $skus = array();

        foreach ($products as $product) {
            $product_id = $product->ID;
            $product_sku = get_post_meta($product_id, '_sku', true);

            if (!empty($product_sku)) {
                $skus[] = $product_sku;
            }
        }

        return $skus;
    }

    // Function to display SKU mappings
    function mirakl_connector_display_sku_mappings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mirakl_sku_mappings';

        $results = $wpdb->get_results("SELECT woocommerce_sku, external_sku, id FROM $table_name");

        if (!empty($results)) {
            echo '<table class="widefat">';
            echo '<thead><tr><th>WooCommerce SKU</th><th>External SKU</th><th>Edit</th></tr></thead>';
            echo '<tbody>';

            foreach ($results as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row->woocommerce_sku) . '</td>';
                echo '<td>' . esc_html($row->external_sku) . '</td>';
                echo '<td><a href="?page=sku-mapping&edit=' . absint($row->id) . '">Edit</a></td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo esc_html('No SKU mappings found.');
        }
    }

    ?>
    <div class="mirakl-settings-wrapper">
        <div class="wrap">
            <h2>SKU Mapping</h2>

            <!-- Two-column layout -->
            <div class="two-column-layout">
                <!-- Left column: Edit SKU mapping -->
                <div class="left-column">
                    <?php
                    if (isset($_GET['edit'])) {
                        $edit_id = intval($_GET['edit']);
                        // Retrieve the mapping for editing
                        $table_name = $wpdb->prefix . 'mirakl_sku_mappings';

                        $mapping_to_edit = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $edit_id");
                        if (!empty($mapping_to_edit)) {
                            ?>
                            <h3>Edit SKU Mapping</h3>
                            <form method="post" action="">
                                <input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>">
                                <table class="form-table">
                                    <tr>
                                        <th>WooCommerce SKU:</th>
                                        <td>
                                            <select name="woocommerce_sku">
                                                <?php
                                                // Get WooCommerce SKUs
                                                $woocommerce_skus = get_woocommerce_skus();

                                                foreach ($woocommerce_skus as $sku) {
                                                    $selected = ($sku == $mapping_to_edit->woocommerce_sku) ? 'selected' : '';
                                                    echo '<option value="' . esc_attr($sku) . '" ' . $selected . '>' . esc_html($sku) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>External SKU:</th>
                                        <td><input type="text" name="external_sku" value="<?php echo esc_attr($mapping_to_edit->external_sku); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <td><input type="submit" class="button-primary" name="update_mapping" value="Update Mapping" /></td>
                                    </tr>
                                </table>
                            </form>
                            <?php
                        }
                    }
                    ?>
                </div>

                <!-- Right column: Add Mapping -->
                <div class="right-column">
                    <h3>Add SKU Mapping</h3>
                    <form method="post" action="">
                        <?php
                        if (isset($_POST['add_mapping'])) {
                            // Handle the form submission to add SKU mappings
                            $external_sku = sanitize_text_field($_POST['external_sku']);
                            $woocommerce_sku = sanitize_text_field($_POST['woocommerce_sku']);

                            // Validate and save the mapping (you can add this logic)
                            save_sku_mapping($external_sku, $woocommerce_sku);
                        }
                        ?>
                        <table class="form-table">
                            <tr>
                                <th>WooCommerce SKU:</th>
                                <td>
                                    <select name="woocommerce_sku">
                                        <?php
                                        // Get WooCommerce SKUs
                                        $woocommerce_skus = get_woocommerce_skus();

                                        foreach ($woocommerce_skus as $sku) {
                                            echo '<option value="' . esc_attr($sku) . '">' . esc_html($sku) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>External SKU:</th>
                                <td><input type="text" name="external_sku" /></td>
                            </tr>
                            <tr>
                                <th></th>
                                <td><input type="submit" class="button-primary" name="add_mapping" value="Add Mapping" /></td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>

            <!-- Display existing SKU mappings -->
            <?php mirakl_connector_display_sku_mappings(); ?>
        </div>
    </div>
    <style>
        /* CSS for the two-column layout */
        .two-column-layout {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 20px;
        }

        .left-column,
        .right-column {
            flex: 1;
        }
    </style>
    <?php
}


function mirakl_connector_stock_management_page() {

    ?>
    <div class="mirakl-settings-wrapper">
        <div class="wrap">
            <h2>Stock Management</h2>

            <!-- Section 1: Product Selection -->
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Select Products:</th>
                        <td>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td><input type="submit" class="button-primary" name="fetch_stock" value="Fetch and Update Stock" /></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
    <?php
}

function mirakl_connector_api_credentials_page() {
    ?>
    <div class="mirakl-settings-wrapper">
        <h2>Mirakl API Settings</h2>

        <!-- Section 1: API Configuration Form -->
        <form method="post" action="">
            <?php
            if (isset($_POST['save_api_config'])) {
                // Retrieve settings from the form
                $mirakl_api_endpoint = sanitize_text_field($_POST['mirakl_api_endpoint']);
                $mirakl_api_key = sanitize_text_field($_POST['mirakl_api_key']);
                // Save the API configuration
                update_option('mirakl_api_endpoint', $mirakl_api_endpoint);

                // Save API credentials to a custom table
                mirakl_connector_api_credentials($mirakl_api_endpoint, $mirakl_api_key);

                echo '<div class="notice notice-success"><p>API configuration saved successfully.</p></div>';
            }
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="mirakl_api_endpoint">API URL:</label></th>
                    <td><input type="text" class="custom-input-field" id="mirakl_api_endpoint" name="mirakl_api_endpoint" value="<?php echo esc_attr(get_option('mirakl_api_endpoint')); ?>" required/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="mirakl_api_key">API Key:</label></th>
                    <td><input type="text" class="custom-input-field" id="mirakl_api_key" name="mirakl_api_key" value="<?php echo esc_attr(get_option('mirakl_api_key')); ?>" required/></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" name="save_api_config" value="Save Config" />
            </p>
        </form>
    </div>
    <?php
}
function mirakl_connector_custom_cron_settings_page() {
    ?>
    <div class="wrap">
        <h2>Custom Cron Job Settings</h2>
        <form method="post" action="">
            <label for="cron_time">Select Cron Job Time:</label>
            <select name="cron_time" id="cron_time">
                <option value="10s" <?php selected(get_option('custom_cron_time', 'hourly'), '10s'); ?>>10 Seconds</option>
                <option value="10min" <?php selected(get_option('custom_cron_time', 'hourly'), '10min'); ?>>10 Minutes</option>
                <option value="4hours" <?php selected(get_option('custom_cron_time', 'hourly'), '4hours'); ?>>4 Hours</option>
                <option value="8hours" <?php selected(get_option('custom_cron_time', 'hourly'), '8hours'); ?>>8 Hours</option>
                <option value="12hours" <?php selected(get_option('custom_cron_time', 'hourly'), '12hours'); ?>>12 Hours</option>
                <option value="1day" <?php selected(get_option('custom_cron_time', 'hourly'), '1day'); ?>>1 Day</option>
                <!-- Add more options as needed -->
            </select>
            <p class="submit">
                <input type="submit" name="save_cron_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}

// Save the selected cron time
function mirakl_connector_save_custom_cron_time() {
    if (isset($_POST['save_cron_settings'])) {
        $cron_time = sanitize_text_field($_POST['cron_time']);
        update_option('custom_cron_time', $cron_time);
    }
}
add_action('admin_init', 'mirakl_connector_save_custom_cron_time');

// Add a custom action hook for fetching orders
function schedule_get_orders_cron() {
    $cron_time = get_option('custom_cron_time', 'hourly');

    if (!wp_next_scheduled('get_orders_cron')) {
        wp_schedule_event(time(), $cron_time, 'get_orders_cron');
    }
}
add_action('wp', 'schedule_get_orders_cron');

// Hook the function to the custom action hook
add_action('get_mirakl_orders', 'mirakl_connector_get_orders_from_api');

function mirakl_connector_api_credentials($mirakl_api_endpoint, $mirakl_api_key) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mirakl_settings';

    $wpdb->insert(
        $table_name,
        array(
            'mirakl_api_endpoint' => $mirakl_api_endpoint,
            'mirakl_api_key' => $mirakl_api_key,
        ),
        array('%s', '%s')
    );
}
function mirakl_connector_get_api_credentials() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mirakl_settings';

    $result = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

    if ($result) {
        return array(
            'mirakl_api_endpoint' => $result->mirakl_api_endpoint,
            'mirakl_api_key' => $result->mirakl_api_key,
        );
    }

    return false;
}
function mirakl_connector_get_orders_from_api() {
    $credentials = mirakl_connector_get_api_credentials();
    $base_api = $credentials['mirakl_api_endpoint'];
    $api_key = $credentials['mirakl_api_key'];
    $timestamp = new DateTime();
    $timestamp->sub(new DateInterval('P7D')); // 5 days ago
    $date = $timestamp->format('Y-m-d\TH:i:s\Z');
    $order_state_codes = 'SHIPPING'; // Comma-separated states

    //$response = call_mirakl_api("/orders/?order_state_codes=$order_state_codes&start_date=$date&max=50");
    $response = wp_remote_get( $base_api.'/api/orders/?api_key='.$api_key.'&order_state_codes='.$order_state_codes.'&start_date='.$date.'&max=50', []); //&order_state_codes=SHIPPING,CANCELED

    if (is_array($response) && !is_wp_error($response)) {
        $body = json_decode($response['body'], false);

        if (isset($body->status)) {
            echo $body->message;
            return false;
        }

        $orders = $body->orders;

        foreach ($orders as $order) {
            $shipping_address = [
                'first_name' => $order->customer->firstname,
                'last_name' => $order->customer->lastname,
                'company' => isset($order->customer->company) ? $order->customer->company : '',
                'email' => $order->customer_notification_email,
                'phone' => '',
                'address_1' => isset($order->customer->shipping_address->street_1) ? $order->customer->shipping_address->street_1 : '',
                'address_2' => isset($order->customer->shipping_address->street_2) ? $order->customer->shipping_address->street_2 : '',
                'city' => isset($order->customer->shipping_address->city) ? $order->customer->shipping_address->city : '',
                'state' => isset($order->customer->shipping_address->state) ? $order->customer->shipping_address->state : '',
                'postcode' => isset($order->customer->shipping_address->zip_code) ? $order->customer->shipping_address->zip_code : '',
                'country' => isset($order->shipping_zone_code) ? $order->shipping_zone_code : '',
            ];

            $billing_address = [
                'first_name' => 'Marketplace Manor'. ' '.$order->order_id,
                'last_name' =>$order->customer->firstname. ' '. $order->customer->lastname,
                'company' => isset($order->customer->company) ? $order->customer->company : '',
                'email' => $order->customer_notification_email,
                'phone' => '',
                'address_1' => isset($order->customer->billing_address->street_1) ? $order->customer->billing_address->street_1 : '',
                'address_2' => isset($order->customer->billing_address->street_2) ? $order->customer->billing_address->street_2 : '',
                'city' => isset($order->customer->billing_address->city) ? $order->customer->billing_address->city : '',
                'state' => isset($order->customer->billing_address->state) ? $order->customer->billing_address->state : '',
                'postcode' => isset($order->customer->billing_address->zip_code) ? $order->customer->billing_address->zip_code : '',
                'country' => isset($order->shipping_zone_code) ? $order->shipping_zone_code : '',
            ];

            $products = [];
            foreach ( $order->order_lines as $order_line ) {
//					var_dump( $order_line );
                //$product_id = mirakl_connector_get_product_by_meta_sku($order_line->product_sku);
                $external_sku = $order_line->product_sku;

                // Query the mapping database to get the corresponding WooCommerce SKU
                global $wpdb;
                $table_name = $wpdb->prefix . 'mirakl_sku_mappings';
                $query = $wpdb->prepare("SELECT woocommerce_sku FROM $table_name WHERE external_sku = %s", $external_sku);
                $woocommerce_sku = $wpdb->get_var($query);
                if ($woocommerce_sku) {
                    // WooCommerce SKU found in the mapping database
                    if ($product_id = wc_get_product_id_by_sku($woocommerce_sku)) {
                        $products[] = [
                            'id' =>  wc_get_product($product_id), //$order_line->product_sku, //
                            'qty' => $order_line->quantity,
                        ];
                    } else {
                        echo 'Product not found';
                    }
                } else {
                    // External SKU not found in the mapping database, handle accordingly
                    echo 'Mirakl SKU not found';
                }
            }
            $params = [
                'shipping_address' => $shipping_address,
                'billing_address' => $billing_address,
                'shipping_price' => $order->shipping_price,
                'products' => $products,
                'order_id' => $order->order_id,
            ];

            mirakl_connector_insert_order_to_wc($params);
        }
    }
}
function mirakl_connector_insert_order_to_wc($params) {
    $credentials = mirakl_connector_get_api_credentials();
    $base_api = $credentials['mirakl_api_endpoint'];
    $api_key = $credentials['mirakl_api_key'];
    if (check_mirakl_order_id_exists($params['order_id'])) {
        return false;
    }

    $order = wc_create_order(['status' => 'processing']);

    foreach ($params['products'] as $product) {
        $product_id = wc_get_product($product['id']);
        $product_qty = $product['qty'];
        $order->add_product($product_id, $product_qty);
    }

    $item = new WC_Order_Item_Shipping();
    $item->set_props(
        array(
            'method_title' => "Standardversand",
            'method_id'    => "test",
            'cost'         => $params['shipping_price'],
            'taxes'        => array(),
            'tax'          => array(),
            'calc_tax'     => 'per_order',
            'total'        => $params['shipping_price'],
            'tax_class'    => NULL,
            'total_tax'    => 0,
            'tax_status'   => 'none',
            'meta_data'    => array(),
            'package'      => false,
        )
    );
    $order->add_item($item);
    $order->set_address($params['billing_address'], 'billing');
    $order->set_address($params['shipping_address'], 'shipping');

    // Set order addresses
    // mirakl_connector_set_order_addresses($order, $params['billing_address'], $params['shipping_address']);

    $order->set_payment_method('creditcard');
    $order->update_meta_data('mirakl_order_id', $params['order_id']);
    $order_id = $params['order_id'];

    // Construct the URL to download the documents
    $order_deliverynote_url = "$base_api/api/orders/documents/download/?api_key=$api_key&order_ids=$order_id";

    $order->calculate_totals(false);

    return $order;
}

function check_mirakl_order_id_exists($order_id) {
    $orders = wc_get_orders([
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => 'mirakl_order_id',
        'meta_value' => $order_id,
        'meta_compare' => '=',
    ]);

    return count($orders) > 0;
}

function mirakl_connector_get_product_by_meta_sku($sku){
    $args = array (
        'post_type'  => 'product',
        'posts_per_page'  => -1,
        'meta_query' => array(
            array(
                'value' => $sku,
                'compare' => '='
            ),
        ),
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts()  ) {
        return $query->get_posts()[0]->ID;
    }

    return null;

}

function mirakl_connector_set_order_addresses($order, $billing_address, $shipping_address) {
    $order->set_address($billing_address, 'billing');
    $order->set_address($shipping_address, 'shipping');
}

?>
