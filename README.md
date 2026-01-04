# Heureka Feed Adapter

Smart PHP library for generating valid Heureka.cz XML product feeds. The package provides automatic feed generation, category management, product validation, and seamless integration with Nette Framework.

## 🎯 Key Principles

- **Automatic feed generation** at the `/xml/heureka-feed.xml` endpoint
- **Built-in category management** with official Heureka category tree support
- **Strict input validation** for all product attributes (EAN-13, ISBN-10/13, URLs, etc.)
- **Support for all major Czech delivery providers** (PPL, DPD, Zasilkovna, GLS, etc.)
- **Caching layer** for category data to minimize external API calls
- **Extensible architecture** with custom description renderers and product loaders
- **Full Nette Framework integration** via DIC extension

## 🏗️ Architecture

The package follows a clean separation of concerns with the following main components:

```
┌─────────────────────────────────────────────────────────────────┐
│                    HeurekaFeedExtension                         │
│              (Nette DI Container Extension)                     │
└─────────────────────┬───────────────────────────────────────────┘
                      │ registers
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                      HeurekaManager                             │
│                   (Main Entry Point)                            │
└─────────────────────┬───────────────────────────────────────────┘
                      │ uses
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                       FeedRenderer                              │
│              (XML Output Generation)                            │
├─────────────────────┬───────────────────────────────────────────┤
│                     │                                           │
│   ┌─────────────────▼─────────────────┐                         │
│   │        ProductLoader              │◄──── Your Implementation│
│   │         (Interface)               │                         │
│   └─────────────────┬─────────────────┘                         │
│                     │ returns                                   │
│   ┌─────────────────▼─────────────────┐                         │
│   │       HeurekaProduct[]            │                         │
│   │          (Entities)               │                         │
│   └───────────────────────────────────┘                         │
│                                                                 │
│   ┌───────────────────────────────────┐                         │
│   │     DescriptionRenderer           │◄──── Optional           │
│   │         (Interface)               │                         │
│   └───────────────────────────────────┘                         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     CategoryManager                             │
│        (Heureka Category Tree Management)                       │
├─────────────────────────────────────────────────────────────────┤
│  • Fetches official category XML from Heureka                   │
│  • Caches data for 8 hours                                      │
│  • Provides tree-structured selectbox data                      │
└─────────────────────────────────────────────────────────────────┘
```

### 📦 Components Overview

| Component | Description |
|-----------|-------------|
| `HeurekaFeedExtension` | Nette DI extension that registers all services and sets up automatic feed endpoint |
| `HeurekaManager` | Main orchestrator that triggers feed rendering |
| `FeedRenderer` | Converts product data to valid Heureka XML format |
| `ProductLoader` | Interface you implement to provide your products |
| `CategoryManager` | Manages Heureka's official category tree with caching |
| `HeurekaProduct` | Entity representing a single product with all Heureka attributes |
| `Category` | Entity representing a Heureka category with parent hierarchy |
| `Delivery` | Entity representing delivery options with predefined carrier constants |
| `DescriptionRenderer` | Interface for custom product description formatting |
| `Helpers` | Utility class with EAN-13 and ISBN-10/13 validation methods |

## 📦 Installation

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/heureka-feed) and
[GitHub](https://github.com/baraja-core/heureka-feed).

To install, simply use the command:

```shell
$ composer require baraja-core/heureka-feed
```

You can use the package manually by creating an instance of the internal classes, or register a DIC extension to link the services directly to the Nette Framework.

### Nette Framework Integration

Register the extension in your `config.neon`:

```neon
extensions:
    heurekaFeed: Baraja\Heureka\HeurekaFeedExtension
```

The extension automatically:
- Registers `HeurekaManager`, `CategoryManager`, and `FeedRenderer` as services
- Sets up the feed endpoint at `/xml/heureka-feed.xml`
- Configures Markdown description renderer if `baraja-core/markdown-latte-filter` is available

## 🚀 Basic Usage

### 1. Implement the ProductLoader Interface

Create a class that implements `ProductLoader` to provide your products:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Baraja\Heureka\ProductLoader;
use Baraja\Heureka\HeurekaProduct;
use Baraja\Heureka\CategoryManager;
use Baraja\Heureka\Delivery;

final class MyProductLoader implements ProductLoader
{
    public function __construct(
        private CategoryManager $categoryManager,
        private ProductRepository $productRepository,
    ) {
    }

    /**
     * @return HeurekaProduct[]
     */
    public function getProducts(): array
    {
        $products = [];

        foreach ($this->productRepository->findAllActive() as $product) {
            $heurekaProduct = new HeurekaProduct(
                itemId: (string) $product->getId(),
                product: $product->getName(),
                productName: $product->getFullName(),
                url: 'https://example.com/product/' . $product->getSlug(),
                priceVat: $product->getPrice(),
                category: $this->categoryManager->getCategory($product->getHeurekaCategoryId()),
                manufacturer: $product->getManufacturer(),
            );

            $heurekaProduct->setDescription($product->getDescription());
            $heurekaProduct->setImgUrl($product->getMainImageUrl());
            $heurekaProduct->setDeliveryDate($product->getDeliveryDays());

            if ($product->getEan() !== null) {
                $heurekaProduct->setEan($product->getEan());
            }

            $heurekaProduct->addDelivery(new Delivery(Delivery::PPL, 99.0));
            $heurekaProduct->addDelivery(new Delivery(Delivery::ZASILKOVNA, 59.0, 79.0));

            $products[] = $heurekaProduct;
        }

        return $products;
    }
}
```

### 2. Register Your ProductLoader

In your `config.neon`:

```neon
services:
    - App\Services\MyProductLoader
```

The `FeedRenderer` will automatically detect and use your `ProductLoader` implementation.

### 3. Access the Feed

Once configured, the XML feed is automatically available at:

```
https://your-domain.com/xml/heureka-feed.xml
```

## 📋 HeurekaProduct Entity

The `HeurekaProduct` entity supports all standard Heureka feed attributes:

### Required Attributes (Constructor)

| Attribute | Type | Description |
|-----------|------|-------------|
| `itemId` | string | Unique product identifier (max 36 chars, alphanumeric with `-` and `_`) |
| `product` | string | Product name for matching (max 200 chars) |
| `productName` | string | Exact product name displayed to users (max 200 chars) |
| `url` | string | Absolute URL to product detail page (max 300 chars) |
| `priceVat` | float | Price including VAT |
| `category` | Category | Heureka category entity |
| `manufacturer` | string | Manufacturer/brand name |

### Optional Attributes (Setters)

```php
$product->setDescription('Product description text');
$product->setImgUrl('https://example.com/image.jpg');
$product->setVideoUrl('https://youtube.com/watch?v=xxx');
$product->addImgUrlAlternative('https://example.com/image2.jpg');
$product->setVat(21.0);
$product->setDeliveryDate(3); // days or DateTime instance
$product->setEan('1234567890123');
$product->setIsbn('978-3-16-148410-0');
$product->setProductNo('SKU123');
$product->setHeurekaCpc(1.5); // max 1000
$product->setParams(['Color' => 'Red', 'Size' => 'XL', 'Wireless' => true]);
$product->setItemType('new');
$product->addAccessory('accessory-item-id');
$product->addCustomTag('CUSTOM_TAG', 'value');
```

### Image Requirements

- **Minimum size**: 20x20 pixels
- **Recommended size**: 175x175 pixels
- **Maximum size**: 4096x4096 pixels (2 MB)
- **Video URL**: Only YouTube links are supported (youtube.com or yt.be domains)

### Parameter Handling

Parameters are automatically converted:
- Boolean values become `"ano"` / `"ne"`
- Numeric values **must include units** (e.g., `"100 g"`, not just `100`)

```php
$product->setParams([
    'Color' => 'Blue',
    'Weight' => '500 g',
    'Waterproof' => true,  // becomes "ano"
    'Bluetooth' => false,  // becomes "ne"
]);
```

## 🚚 Delivery Options

The `Delivery` entity supports all major Czech carriers:

```php
use Baraja\Heureka\Delivery;

// Basic delivery
$delivery = new Delivery(Delivery::PPL, 99.0);

// Delivery with COD (cash on delivery) price
$delivery = new Delivery(Delivery::ZASILKOVNA, 59.0, 79.0);

$product->addDelivery($delivery);
```

### Supported Carriers

| Constant | Carrier |
|----------|---------|
| `CESKA_POSTA` | Ceska posta - Balik Do ruky |
| `CESKA_POSTA_NAPOSTU_DEPOTAPI` | Ceska posta - Balik Na postu |
| `CESKA_POSTA_DOPORUCENA_ZASILKA` | Ceska posta - Doporucena zasilka |
| `PPL` | PPL |
| `DPD` | DPD |
| `DPD_PICKUP` | DPD Pickup |
| `DHL` | DHL |
| `GLS` | GLS |
| `GEIS` | Geis |
| `ZASILKOVNA` | Zasilkovna |
| `BALIKOVNA_DEPOTAPI` | Balikovna |
| `UPS` | UPS |
| `FEDEX` | FedEx |
| `TNT` | TNT |
| `TOPTRANS` | TOPTRANS |
| `WEDO` | WeDo (IN TIME & Ulozhenka) |
| `VLASTNI_PREPRAVA` | Custom delivery |
| `FOFR` | FOFR |
| `DSV` | DSV |
| `HDS` | HDS |
| `GEBRUDER_WEISS` | Gebruder Weiss |
| `SEEGMULLER` | Seegmuller |
| `RABEN_LOGISTICS` | Raben Logistics |
| `CSAD_LOGISTIK_OSTRAVA` | CSAD Logistik Ostrava |

## 📁 Category Management

The `CategoryManager` provides access to Heureka's official category tree:

```php
use Baraja\Heureka\CategoryManager;

class MyService
{
    public function __construct(
        private CategoryManager $categoryManager,
    ) {
    }

    public function example(): void
    {
        // Get a specific category by ID
        $category = $this->categoryManager->getCategory(123);

        // Get all categories as flat array
        $allCategories = $this->categoryManager->getCategories();

        // Get categories formatted for selectbox (id => name)
        $selectableCategories = $this->categoryManager->getSelectableCategories();

        // Get categories as tree-structured selectbox (with indentation)
        $selectboxTree = $this->categoryManager->getCategoriesSelectbox();
    }
}
```

### Category Entity

```php
$category->getId();           // int - Category ID
$category->getName();         // string - Category name
$category->getParent();       // ?Category - Parent category
$category->getParentId();     // ?int - Parent category ID
$category->getCategoryText(); // string - Full path (e.g., "Heureka.cz | Electronics | Phones")
$category->getParentsPath();  // array - [id => name] of all parents
```

### Custom Category Feed URL

By default, categories are fetched from `https://www.heureka.cz/direct/xml-export/shops/heureka-sekce.xml`. You can change this:

```php
$categoryManager->setFeedUrl('https://custom-feed-url.com/categories.xml');
```

### Caching

- Category feed is cached for **8 hours**
- Selectbox tree is cached for **1 hour**
- Uses Nette Caching with configurable storage

## 🎨 Custom Description Renderer

Implement `DescriptionRenderer` to customize how product descriptions are processed:

```php
use Baraja\Heureka\DescriptionRenderer;

final class HtmlStripperDescriptionRenderer implements DescriptionRenderer
{
    public function render(string $haystack): string
    {
        return strip_tags($haystack);
    }
}
```

Register your implementation:

```neon
services:
    - HtmlStripperDescriptionRenderer
```

Then set it on the `FeedRenderer`:

```php
$feedRenderer->setDescriptionRenderer($myRenderer);
```

### Built-in Markdown Renderer

If `baraja-core/markdown-latte-filter` is installed, the `BarajaMarkdownDescriptionRenderer` is automatically registered. It converts Markdown to plain text (strips HTML tags after rendering).

## 🔧 Validation Helpers

The `Helpers` class provides validation utilities:

```php
use Baraja\Heureka\Helpers;

// Validate EAN-13 barcode
Helpers::validateEAN13('1234567890123'); // bool

// Validate ISBN-10
Helpers::isValidIsbn10('0-306-40615-2'); // bool

// Validate ISBN-13
Helpers::isValidIsbn13('978-3-16-148410-0'); // bool
```

These validators are automatically used when setting `EAN` or `ISBN` on `HeurekaProduct` and will throw `InvalidArgumentException` for invalid values.

## 🔍 Generated XML Structure

The generated feed follows Heureka's XML specification:

```xml
<?xml version="1.0" encoding="utf-8"?>
<SHOP>
    <SHOPITEM>
        <ITEM_ID>product-123</ITEM_ID>
        <PRODUCT>Product Name</PRODUCT>
        <PRODUCTNAME>Full Product Name</PRODUCTNAME>
        <DESCRIPTION>Product description</DESCRIPTION>
        <URL>https://example.com/product/123</URL>
        <IMGURL>https://example.com/images/product.jpg</IMGURL>
        <IMGURL_ALTERNATIVE>https://example.com/images/product-2.jpg</IMGURL_ALTERNATIVE>
        <PRICE_VAT>1299</PRICE_VAT>
        <VAT>21</VAT>
        <MANUFACTURER>Brand Name</MANUFACTURER>
        <CATEGORYTEXT>Heureka.cz | Electronics | Phones</CATEGORYTEXT>
        <DELIVERY_DATE>3</DELIVERY_DATE>
        <EAN>1234567890123</EAN>
        <PARAM>
            <PARAM_NAME>Color</PARAM_NAME>
            <VAL>Red</VAL>
        </PARAM>
        <DELIVERY>
            <DELIVERY_ID>PPL</DELIVERY_ID>
            <DELIVERY_PRICE>99</DELIVERY_PRICE>
        </DELIVERY>
        <DELIVERY>
            <DELIVERY_ID>ZASILKOVNA</DELIVERY_ID>
            <DELIVERY_PRICE>59</DELIVERY_PRICE>
            <DELIVERY_PRICE_COD>79</DELIVERY_PRICE_COD>
        </DELIVERY>
    </SHOPITEM>
</SHOP>
```

## ⚠️ Error Handling

The package performs extensive validation and throws exceptions for invalid data:

- `InvalidArgumentException` - For invalid attribute values (URLs, EAN, ISBN, lengths, etc.)
- `LogicException` - For duplicate `ItemId` values in the feed
- `RuntimeException` - When `ProductLoader` is not configured

All validation errors include descriptive messages to help identify the issue.

## 📝 Manual Usage (Without Nette)

```php
use Baraja\Heureka\FeedRenderer;
use Baraja\Heureka\CategoryManager;
use Nette\Http\Response;
use Nette\Caching\Storages\MemoryStorage;

// Create dependencies
$storage = new MemoryStorage();
$response = new Response();
$categoryManager = new CategoryManager($storage);

// Create renderer
$feedRenderer = new FeedRenderer($response);
$feedRenderer->setProductLoader(new MyProductLoader($categoryManager));

// Render feed
$feedRenderer->render();
```

## 👤 Author

**Jan Barasek** - [https://baraja.cz](https://baraja.cz)

## 📄 License

`baraja-core/heureka-feed` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/heureka-feed/blob/master/LICENSE) file for more details.
