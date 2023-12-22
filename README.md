# Mirakl WooCommerce Connector Plugin

<a href="https://www.buymeacoffee.com/mjabane" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/default-orange.png" alt="Buy Me A Coffee" height="41" width="174"></a>


**Plugin Name:** Mirakl WooCommerce Connector<br>
**Description:** Connect Mirakl with WooCommerce.<br>
**Version:** 1.0.0<br>
**Author:** Mohamed Ayoub Jabane & Abdellatif Bouziane<br>
**Tested up to:** 8.4<br>
**WC requires at least:** 3.0<br>
**WC tested up to:** 8.4<br>
**Requires at least:** 5.6

---

## How To Use

Download the plugin zip file and upload it to your Woocommerce shop.<br>
Activate it and navigate to > `API Credentials`<br>
Insert your `mirakl API url` ` 'https://xxx-prod.mirakl.net'`<br>
Insert your `API Key` `'xxxxx'`<br>
Yeah ╰(*°▽°*)╯ you are ready to start.<br>
FYI: Regarding the `tracking code confirmations` being sent to Mirakl, unfortunately, aren't set but we can customize it for `DHL` in the `next release`.

---

## Activation Hook

When activated, this plugin creates two database tables: `mirakl_settings` and `mirakl_sku_mappings` for configuration settings and SKU mappings.

---

## Admin Menu

The plugin adds various admin menu items:

- **Mirakl Connector:** Main menu item.
- **Channels Orders Import:** Submenu for importing Mirakl orders.
- **SKU Mapping:** Submenu for managing SKU mappings.
- **Stock Management:** Submenu for stock management.
- **API Credentials:** Submenu for configuring Mirakl API credentials.
- **Import Orders Settings:** Submenu for configuring custom cron job settings.

---

## Channels Orders Import Page

This page allows users to trigger the retrieval of Mirakl orders by clicking the "Get Mirakl Orders" button.

---

## SKU Mapping Page

- Users can manage SKU mappings between WooCommerce and Mirakl.
- Features a two-column layout for editing mappings and adding new ones.
- Users can edit existing mappings and add new ones with validation and saving handled in the code.
- Existing mappings are displayed in a table.

---

## Stock Management Page

A page for potential future stock management features.

---

## API Credentials Page

- Users can configure Mirakl API settings, including the API endpoint and API key.
- The settings are saved for future retrieval.

---

## Custom Cron Settings Page

- Allows users to configure custom cron job settings for Mirakl order retrieval.
- The selected cron time is saved for scheduling.

---

## Custom Functions

Various custom functions for database operations, API interactions, and WooCommerce order creation. The plugin fetches Mirakl orders, maps them to WooCommerce products using SKU mappings, and creates orders in WooCommerce. Functions for checking if Mirakl order IDs exist in WooCommerce orders.

---

## Hooks and Actions

Extensive use of WordPress hooks and actions to execute functions at different points during the WordPress lifecycle.

This code serves as the foundation for integrating a WooCommerce store with the Mirakl marketplace, enabling product and order synchronization. Additional development and testing may be required for your specific environment.
