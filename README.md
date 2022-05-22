# API Extensions Bundle

Set of Symfony extensions to improve API development. 
Currently consists of one major extension.

## Version Fallback

Provides better Developer Experience by auto-resolving already implemented methods.

#### To-do:
- [ ] Dedicated configuration file
- [ ] Multiple APIs

### Use case

Your team once released a large set of methods and named it *Store API v1*.

Months later, you need to change set of required fields or completely change the structure of your response. 
You will (in most cases) start developing it as *Store API v2*, not even touching previous version.

Well, your team changed some of previously created methods. What about 10, 20 or 100 untouched methods you've implemented before? 
Copy and paste them into your `v2` directory? May be enough for simple APIs,
but when your API is actively developed, you should probably have better option, such as this extension. 


### Algorithm

*TL;DR. No work performed at runtime, everything is compiled like most of your application routes.*

- Detect maximum supported version
- Retrieve all routes list
- Filter routes related to API
- Group routes by version-less name (version is replaced with placeholder)
- Detect first implemented version
- Loop up through every version, skipping already implemented or copying previously implemented route

### Requirements

#### Route naming

**Warning: Only major versions supported** (format `v1.1` or `v12.32.1` is not supported currently)

You **must** give names for routes which you want to be supported by this bundle.
Single route name **must** follow next requirements:

- Starts with `app_`
- Includes `_api_v` with defined version

It's recommended to keep naming consistent across versions.

##### Examples
- `app_products_api_v1_get_products`
- `app_api_v1_users_get`
- `app_api_v13_user_create`


### Installation


1. Run `composer require artemijdeleto/api-extensions-bundle`
2. Go to `src/Kernel.php`
3. Paste code snippet below
```php
<?php

namespace App;

use Deleto\VersioningBundle\Kernel\Kernel as VersioningKernel;

class Kernel extends VersioningKernel
{
}
```

3. Define `deleto.api.max_version` in your `config/services.yaml`

That's it.
