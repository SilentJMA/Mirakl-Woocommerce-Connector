<p align="center">
  <h1 align="center">Mirakl WooCommerce Connector Pro</h1>
  <p align="center">
    <em>Enterprise-grade bridge between WooCommerce and 400+ Mirakl-powered marketplaces</em>
  </p>
</p>

<p align="center">
  <a href="#"><img alt="Version" src="https://img.shields.io/badge/version-5.0.0-1f6ebf" /></a>
  <a href="#requirements"><img alt="WordPress" src="https://img.shields.io/badge/WordPress-5.6%2B-21759b" /></a>
  <a href="#requirements"><img alt="WooCommerce" src="https://img.shields.io/badge/WooCommerce-3.0%2B-7f54b3" /></a>
  <a href="#requirements"><img alt="PHP" src="https://img.shields.io/badge/PHP-8.3%2B-777bb4" /></a>
  <a href="LICENSE"><img alt="License" src="https://img.shields.io/badge/license-GPLv3-2ea44f" /></a>
</p>

> **🔒 Private Plugin** — This is a proprietary WooCommerce extension. To request access, please fill out [this form](https://docs.google.com/forms/d/e/1FAIpQLSeLrpUuZq8bg5cUAco6WeLdUfQ1tgd2oUWiTeStiqRyAlPxEA/viewform?usp=publish-editor).

---

## Overview

Bidirectional sync engine between WooCommerce and the [Mirakl MMP](https://mirakl.com) platform. Manage products, inventory, pricing, orders, shipping, and delivery notes across multiple Mirakl marketplaces — all from your WordPress dashboard.

Built on the official `mirakl/sdk-php-shop` with a WordPress-native HTTP transport (no cURL dependency), retry middleware, and rate-limit tracking.

## Features

- **Multi-store** — Connect unlimited Mirakl stores to one WooCommerce instance
- **Product export** — Push catalog, brand, GTIN, and categories via OF24 API
- **Inventory sync** — Cron-based stock updates with aside-inventory reservation
- **Pricing sync** — Bulk price imports via PRI01 with status tracking
- **Order import** — Auto-create WC orders from marketplace sales, email suppression
- **Delivery notes** — Built-in PDF generator (Dompdf, 5 languages, per-store logo)
- **Attribute mapping** — Two-panel AJAX UI with category tree, value mapping, VL11 autocomplete
- **Carrier sync** — Fetch and map carrier codes from CA01 API
- **Refund processing** — Marketplace refunds via RE01 API
- **Cron automation** — Per-type intervals: 5/10/15/30 min, hourly; manual triggers
- **Sync history & health** — Full audit trail, API connectivity diagnostics

## Requirements

| Dependency | Minimum |
|------------|---------|
| WordPress | 5.6+ |
| WooCommerce | 3.0+ |
| PHP | 8.3+ (ext: `json`, `mbstring`, `gd`, `dom`) |
| Memory | 256 MB (512 MB for large catalogs) |

## Quick Start

1. Upload to `wp-content/plugins/mirakl-woocommerce-connector` and activate
2. Navigate to **Mirakl → Stores**, click **Add New Store**
3. Enter your API endpoint, API key, and store name
4. Click **Test Connection** to verify, then save
5. Go to **Mirakl → Sync Schedule** to enable syncs and set intervals

> Composer dependencies are pre-bundled — no `composer install` required.

## Admin Pages

| Page | Slug | Purpose |
|------|------|---------|
| Dashboard | `mirakl-connector` | Store stats, last sync |
| Orders | `mirakl-connector-orders` | Imported order management |
| Refunds | `mirakl-connector-refunds` | Process refunds/credit notes |
| Stock | `mirakl-connector-stock` | Inventory sync |
| Prices | `mirakl-connector-price` | Price sync + import tracking |
| Product Export | `mirakl-connector-product-export` | Catalog export |
| Carriers | `mirakl-connector-carriers` | Carrier configurations |
| Attribute Mapping | `mirakl-connector-attribute-mapping` | Map WC ↔ Mirakl attributes |
| Stores | `mirakl-connector-settings` | Credentials, locale, shipping |
| Sync Schedule | `mirakl-connector-sync-settings` | Cron intervals per type |
| Sync Logs | `mirakl-connector-logs` | Filterable operation history |
| API Health | `mirakl-connector-health` | Connectivity diagnostics |

## Architecture

```
src/                    # PSR-4 autoloaded (Mirakl\ namespace)
├── Core/               # DI container, hooks, query builder
├── Api/                # Client, Orders, Products, Offers, Returns, Refunds, Carriers
├── Sync/               # Order, Stock, Price sync operations
├── Repository/         # DB repositories (SkuCache, SyncLock, PricingImport)
├── Admin/              # Admin controllers (Dashboard, Stores)
└── DeliveryNote/       # PDF generator (OrderData, Generator)

includes/               # Legacy stack (singletons, main operational code)
├── bootstrap.php       # Dependency-ordered loader
├── sdk/                # WordPress handler, retry, rate-limit, log middleware
├── class-mirakl-connector.php
├── class-mirakl-admin.php
├── class-mirakl-api.php / class-mirakl-api-client.php
├── class-mirakl-logger.php / class-mirakl-sku-resolver.php
├── class-mirakl-pricing-import-service.php / class-mirakl-refund-service.php
├── class-mirakl-cli.php / class-mirakl-sync-lock.php
└── class-mirakl-attribute-sync.php
```

### SDK Middleware Stack (innermost → outermost)

`WordPressHandler → Retry (3× exp. backoff + jitter) → RateLimit → Log`

## Hooks

| Hook | Type | Description |
|------|------|-------------|
| `mirakl_connector_loaded` | Action | Plugin fully loaded |
| `mirakl_connector_order_shipped` | Action | WC order completed |
| `mirakl_connector_api_error` | Action | API error occurred |
| `mirakl_connector_api_credentials` | Filter | Override store credentials |
| `mirakl_connector_attribute_sync_locale` | Filter | PM11 locale override |
| `mirakl_connector_allowed_product_types` | Filter | Allowed product types for sync |
| `mirakl_format_product_data` | Filter | Modify product data before export |

## Database

All tables use `{$wpdb->prefix}mirakl_*` naming. Created/updated on activation, non-destructive migrations.

| Table | Purpose |
|-------|---------|
| `mirakl_api_settings` | Per-store credentials, locale, shipping config |
| `mirakl_orders` | Order mapping and status tracking |
| `mirakl_sync_history` | Audit trail (all sync types) |
| `mirakl_carriers` / `mirakl_carrier_mapping` | Carrier codes and channel mappings |
| `mirakl_channel_categories` | Category hierarchy cache |
| `mirakl_product_mappings` | Product ID cross-references |
| `mirakl_offer_sku_cache` | OF21 SKU resolution (auto-populated) |
| `mirakl_pricing_imports` | PRI01 import lifecycle |
| `mirakl_attributes` | PM11 + VL11 attribute cache |
| `mirakl_fetch_times` / `mirakl_tracking_update_times` | Fetch cursors |

## Development

```bash
composer install              # Install dev deps (PHPUnit, PHPCS)
vendor/bin/phpunit tests/Unit/
php tests/verify-v50.php
```

Submit PRs against the `develop` branch. Follow WordPress Coding Standards.

## License

GPLv3 — see [LICENSE](LICENSE).
