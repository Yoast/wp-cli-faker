# WP CLI Faker

This package introduces two new WP CLI commands to generate fake data.

## Installing

This command can be installed for WP CLI by running:

`wp package install git@github.com:herregroen/wp-cli-faker.git`.

In many cases the default memory limit will not be enough to run composer so running the following instead is generally recommended:

`php -d memory_limit=512M "$(which wp)" package install git@github.com:herregroen/wp-cli-faker.git`


## Core

The `wp faker core content` command generates authors, attachments, categories, tags, posts and pages. It supports the following flags:
- authors: The number of authors to generate, by default 10.
- attachments: The number of attachments to generate, by default 10.
- attachment_keyword: The keyword to search for on loremflickr.com, by default wordpress.
- categories: The number of categories to generate, by default 10.
- tags: The number of tags to generate, by default 25.
- posts: The number of posts to generate, by default 100.
- pages: The number of pages to generate, by default 5.

## WooCommerce

The `wp faker woocommerce products` command generates attachments, product categories, brands ( if Perfect WooCommerce Brands is installed ), products and review. It supports the following flags:
- attachments: The number of attachments to generate, by default 10.
- attachment_keyword: The keyword to search for on loremflickr.com, by default wordpress.
- categories: The number of product categories to generate, by default 25.
- brands: The number of brands to generate, by default 25.
- products: The number of products to generate, by default 300.
- min-reviews: The minimum number of reviews to generate per product, by default 3.
- max-reviews: The maximum number of reviews to generate per product, by default 8.
