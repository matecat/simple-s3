# Simple S3 Client

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/c131920fb28d46ce8ee0a629099d2bdf)](https://app.codacy.com/app/mauretto78_2/simple-s3?utm_source=github.com&utm_medium=referral&utm_content=mauretto78/simple-s3&utm_campaign=Badge_Grade_Settings)
[![license](https://img.shields.io/github/license/mauretto78/simple-s3.svg)]()
[![Packagist](https://img.shields.io/packagist/v/mauretto78/simple-s3.svg)]()
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mauretto78/simple-s3/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mauretto78/simple-s3/?branch=master)

**Simple S3 Client** is a simple wrapper of the official SDK PHP Client.

## Basic Usage

To instantiate the Client do the following:

```php
use SimpleS3\Client;

$s3Client = new Client(
    $access_key_id,
    $secret_key,
    $config = [
        'version' => $verion,
        'region' => $region,
    ]
);
```

You MUST provide your ```$access_key_id``` and ```$secret_key```, plus an optional ```$config``` array.

For further details please refer to the official documentation:

[Configuration for the AWS SDK for PHP Version 3](https://docs.aws.amazon.com/en_us/sdk-for-php/v3/developer-guide/guide_configuration.html#credentials)

## Methods

Here is the list of Client's public methods:

*   `clearBucket` - clear a bucket from all files
*   `copyItem` - copy an item from a bucket to another one
*   `copyInBatch` - copy in batch items from a bucket to another one
*   `createBucketIfItDoesNotExist` . create a bucket if it does not exists
*   `createFolder` . create an empty folder in a bucket if it does not exists
*   `deleteBucket` - delete a bucket
*   `deleteFolder` - delete a folder
*   `deleteItem` - delete an item
*   `downloadItem` - download an item
*   `enableAcceleration` - enable the acceleration mode for a bucket
*   `enableVersioning` - enable the versioning for a bucket
*   `getBucketLifeCycle` get the bucket lifecycle configuration
*   `getBucketSize` get the size (in Bytes) of files in a bucket
*   `getItem` - get all informations for an item
*   `getItemsInABucket` get an array of items in a bucket
*   `getPublicItemLink` - get the public link to download the item
*   `hasBucket` - check if a bucket exists
*   `hasFolder` - check if a folder exists
*   `hasItem` - check if an item exists
*   `openItem` - get the content of an item
*   `restoreItem` - try to restore an item from archive
*   `setBucketLifecycleConfiguration` - set bucket lifecycle configuration
*   `transfer` - transfer content from/to buckets
*   `uploadItem` - upload an item to a bucket from a file
*   `uploadItemFromBody` - upload an item to a bucket from the body content

## Naming validation for buckets and objects

Please refer to the official AWS policy:

*   [Bucket naming restrictions and limitations](https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/BucketRestrictions.html)
*   [Object safe naming rules](https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/UsingMetadata.html)

The Client comes with two validators:
 
*    ```S3BucketNameValidator``` 
*    ```S3ObjectSafeNameValidator``` 
 
These two classes throws you an ```InvalidS3NameException``` if the name provided is not compliant with the AWS rule conventions. 

Validators are invoked in Client's ```createBucketIfItDoesNotExist```, ```uploadFileFromBody``` and ```uploadFile``` methods.

## Object name escaping

To be compliant with [object safe naming rules](https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/UsingMetadata.html) the Client comes with a class named ```S3ObjectSafeNameEncoder``` which automatically encodes/decodes the strings. Consider this example:

```php
...

use SimpleS3\Components\Encoders\S3ObjectSafeNameEncoder;

$unsafeString = 'folder/to/{unsafe}/[EN][] test_dummy.txt';

// returns folder/to/3gTZd4unsafe5*JX(X/wL5HoaENOtdJXDwL5HoaOtdJXDUvPRTItest_dummy.txt
$safeString = S3ObjectSafeNameEncoder::encode($unsafeString);
```

## Bucket lifecycle

You can set the basic lifecycle for your bucket with ```setBucketLifecycleConfiguration``` method. 

This method is automatically invoked when you try to create a new bucket with ```createBucketIfItDoesNotExist``` method.

For further details please refer to the official documentation:

[Bucket lifecycle configuration complete reference](https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-lifecycle-configuration.html)

## Restoring an item

You can use ```restoreItem``` to send a restore an archived object. You can choose between three retrieval options:

*    ```Bulk``` (lasts 5-12 hours)
*    ```Expedited``` (default, lasts 1-5 minutes)
*    ```Standard``` (lasts 3-5 hours)

For further details please refer to the official documentation:

[Restore an archived object](https://docs.aws.amazon.com/cli/latest/reference/s3api/restore-object.html)

## Caching

In order speed up data retrieval, you can inject a cache handler. Please note that the cache MUST implement ```SimpleS3\Components\Cache\CacheInterface```.
The client comes with a Redis implementation:

```php
...

use SimpleS3\Components\Cache\RedisCache;

$redis = new Predis\Client();
$cacheAdapter = new RedisCache($redis);
$s3Client->addCache($cacheAdapter);
```

Now ```getItemsInABucket``` method will get the elements directly from cache. Please note that caching works ONLY if you provide a prefix to the method:

```php
...

// this will get keys from cache
$s3Client->getItemsInABucket([
    'bucket' => 'your-bucket', 
    'prefix' => 'prefix/',
    'hydrate' => true // false by default. If true is set the method returns an array of Aws\ResultInterface 
]);

// this will EVER get keys from S3
$s3Client->getItemsInABucket('your-bucket');

```

## Logging

You can inject your logger to log every Client outcome call. Please note that your logger MUST be PSR-3 compliant:

```php
...

// $logger MUST implement Psr\Log\LoggerInterface

$s3Client->addLogger($logger); 
```

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/mauretto78/simple-s3/issues).

## Authors

* **Mauro Cassani** - [github](https://github.com/mauretto78)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
