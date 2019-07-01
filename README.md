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

$s3Client = new Client([
    'version' => 'latest',   // REQUIRED 
    'region' => 'us-west-2', // REQUIRED
    'credentials' => [       // OPTIONAL 
        'key' => 'YOUR-ACCESS-KEY', 
        'secret' => 'YOUR-SECRET-KEY', 
        'token' => 'SESSION-TOKEN', 
    ]
];
```

Please note that if you don't provide the credentials array, the Client will try to [get values 
from the following environments variables on your system as the original S3Client](https://docs.aws.amazon.com/en_us/sdk-for-php/v3/developer-guide/guide_credentials_environment.html):

* `AWS_ACCESS_KEY_ID`
* `AWS_SECRET_ACCESS_KEY`
* `AWS_SESSION_TOKEN`

If you instead want to authenticate assuming an IAM Role in another AWS Account do the following:

```php
use SimpleS3\Client;

$s3Client = new Client([
    'version' => 'latest',
    'region' => 'us-west-2',
    'iam' => [ 
        'arn' => 'arn:aws:iam::123456789012:role/xaccounts3acces', 
        'session' => 's3-access-example', 
    ]
];
```

For further config details please refer to the official documentation:

[Configuration for the AWS SDK for PHP Version 3](https://docs.aws.amazon.com/en_us/sdk-for-php/v3/developer-guide/guide_configuration.html#credentials)

## Methods

Here is the list of Client's public methods:

*   `clearBucket` - clear a bucket from all files
*   `copyItem` - copy an item from a bucket to another one
*   `copyInBatch` - copy in batch items from a bucket to another one
*   `createBucketIfItDoesNotExist` . create a bucket if it does not exists
*   `createFolder` . create an empty folder in a bucket if it does not exists
*   `deleteBucket` - delete a bucket
*   `deleteBucketPolicy` - delete the bucket policy
*   `deleteFolder` - delete a folder
*   `deleteItem` - delete an item
*   `downloadItem` - download an item
*   `enableAcceleration` - enable the acceleration mode for a bucket
*   `getBucketLifeCycle` get the bucket lifecycle configuration
*   `getBucketPolicy` get the bucket policy
*   `getBucketSize` get the size (in Bytes) of files in a bucket
*   `getItem` - get all informations for an item
*   `getItemsInABucket` get an array of items in a bucket
*   `getCurrentItemVersion` - get the latest version of an item
*   `getPublicItemLink` - get the public link to download the item
*   `hasBucket` - check if a bucket exists
*   `hasFolder` - check if a folder exists
*   `hasItem` - check if an item exists
*   `isBucketVersioned` - check if bucket has versioned enabled
*   `openItem` - get the content of an item
*   `restoreItem` - try to restore an item from archive
*   `setBucketLifecycleConfiguration` - set bucket lifecycle configuration
*   `setBucketPolicy` - set the bucket policy 
*   `setBucketVersioning` - set the bucket versioning 
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

## Objects name escaping

Please read carefully the [object safe naming rules](https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/UsingMetadata.html). 

Escaping object names is entirely up to you.

You can use the provided ```SimpleS3\Components\Encoders\UrlEncoder``` class, or inject in Client your own encoder if you prefer, but please note that it MUST implement 
```SimpleS3\Components\Encoders\SafeNameEncoderInterface``` 
interface:

```php
...

use SimpleS3\Components\Encoders\UrlEncoder;

$encoder = new UrlEncoder();
$s3Client->addEncoder($encoder);
```

## Bucket lifecycle

You can set the basic lifecycle for your bucket with ```setBucketLifecycleConfiguration``` method. 

```php
...

$s3Client->setBucketLifecycleConfiguration(['bucket' => $this->bucket, 'rules' => [...]]);
```

For further details please refer to the [bucket lifecycle configuration official API documentation](https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-lifecycle-configuration.html):


## Bucket versioning

You can enable bucket versioning:

```php
...

$s3Client->setBucketVersioning(['bucket' => $this->bucket]);
```

And now, when you use ```getItemsInABucket``` method, a <VERSION_ID> tag will be added to keys:

```php
...

// getItemsInABucket() will return something like this:
$notHydrated = [
    'key<VERSION_ID=123456789>',
    'key<VERSION_ID=234567890>',
    'key<VERSION_ID=345678901>',
];

$hydrated = [
    'key<VERSION_ID=123456789>' => 'content',
    'key<VERSION_ID=234567890>' => 'content',
    'key<VERSION_ID=345678901>' => 'content',
];
```

For further details please refer to the [bucket versioning  official API documentation](https://docs.aws.amazon.com/en_us/AmazonS3/latest/API/RESTBucketPUTVersioningStatus.html).


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

## Commands

If you have an application which uses [Symfony Console](https://github.com/symfony/console), you have some commands available:

*  ```ss3:batch:transfer```  Transfer files from/to a bucket.
*  ```ss3:bucket:clear```    Clears a bucket.
*  ```ss3:bucket:create```   Creates a bucket.
*  ```ss3:bucket:delete```   Deletes a bucket.
*  ```ss3:cache:flush```     Flush all data stored in cache.
*  ```ss3:cache:stats```     Get the cache statistics.
*  ```ss3:item:copy```       Copy an object from a bucket to another one.
*  ```ss3:item:delete```     Deletes an object from a bucket.
*  ```ss3:item:download```   Download an object from a bucket.
*  ```ss3:item:upload```     Upload an object into a bucket.

You can register the commands in your app, consider this example:

```php
#!/usr/bin/env php
<?php
set_time_limit(0);

...

$redis = new Predis\Client();
$cacheAdapter = new \SimpleS3\Components\Cache\RedisCache($redis);
$s3Client->addCache($cacheAdapter);

// create symfony console app
$app = new \Symfony\Component\Console\Application('Simple S3', 'console tool');

// add commands here
$app->add(new \SimpleS3\Console\BatchTransferCommand($s3Client));
$app->add(new \SimpleS3\Console\BucketClearCommand($s3Client));
$app->add(new \SimpleS3\Console\BucketCreateCommand($s3Client));
$app->add(new \SimpleS3\Console\BucketDeleteCommand($s3Client));
$app->add(new \SimpleS3\Console\CacheFlushCommand($s3Client));
$app->add(new \SimpleS3\Console\CacheStatsCommand($s3Client));
$app->add(new \SimpleS3\Console\ItemCopyCommand($s3Client));
$app->add(new \SimpleS3\Console\ItemDeleteCommand($s3Client));
$app->add(new \SimpleS3\Console\ItemDownloadCommand($s3Client));
$app->add(new \SimpleS3\Console\ItemUploadCommand($s3Client));

$app->run();
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
