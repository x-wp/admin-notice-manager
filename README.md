<div style="text-align:center">

# Admin Notice Manager 🔔

### Simplifies the process of creating, displaying and saving notices in WordPress admin.

[![Packagist Version](https://img.shields.io/packagist/v/x-wp/admin-notice-manager)](https://packagist.org/packages/x-wp/admin-notice-manager)
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/x-wp/admin-notice-manager/php?logo=php&logoColor=white&logoSize=auto)
![Static Badge](https://img.shields.io/badge/WP-%3E%3D6.4-3858e9?style=flat&logo=wordpress&logoSize=auto)

</div>

Displaying notices in WordPress admin is a common task for plugin and theme developers. This package provides an easy to use API for notice management.

## How to use

### Installation

You can install the package via composer:

```bash
composer require x-wp/admin-notice-manager
```

> [!TIP]
> We recommend using the `automattic/jetpack-autoloader` with this package to prevent autoloading issues.

### Configuration and initialization

Package will automatically initialize itself on `admin_init` hook. Manager comes with batteries included - you will use the Notice class to create and display notices.

### Examples

#### Creating a notice

If you are certain that no invoice has the same id as the one you are creating, you can use the `xwp_create_notice` function to create a notice.

```php
<?php

xwp_create_notice(
  array(
    'id' => 'my-plugin-notice',
    'type' => 'info',
    'message' => 'This is an informational notice.',
    'persistent' => true,
    'dismissible' => true,
  )
);
```

Alternatively you can use the `xwp_get_notice` function to try retrieving a notice by its id - and create it if it doesn't exist.

```php
<?php

xwp_get_notice('my-plugin-notice')
  ->set_props(
    array(
    'id' => 'my-plugin-notice',
    'type' => 'info',
    'message' => 'This is an informational notice.',
    'persistent' => true,
    'dismissible' => true,
    )
  )
  ->save();
```
