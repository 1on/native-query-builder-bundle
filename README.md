# IntaroNativeQueryBuilderBundle #

## About ##

## Installation ##

Require the bundle in your composer.json file:

````
{
    "require": {
        "intaro/native-query-builder-bundle": "dev-master",
    }
}
```

Register the bundle:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        new Intaro\NativeQueryBuilderBundle\IntaroNativeQueryBuilderBundle(),
    );
}
```

Install the bundle:

```
$ composer update intaro/native-query-builder-bundle
```


## Usage ##