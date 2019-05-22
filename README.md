# Simple S3 Client

[![license](https://img.shields.io/github/license/mauretto78/simple-s3.svg)]()
[![Packagist](https://img.shields.io/packagist/v/mauretto78/simple-s3.svg)]()
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mauretto78/simple-s3/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mauretto78/simple-s3/?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/0cf7b903fee24738a834390361105cf5)](https://www.codacy.com/app/mauretto78_2/simple-s3?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=mauretto78/simple-s3&amp;utm_campaign=Badge_Grade)

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
*   `copyFile` - copy a file from a bucket to another one
*   `copyInBatch` - copy in batch files from a bucket to another one
*   `createBucketIfItDoesNotExist` . create a bucket if it does not exists
*   `deleteBucket` - delete a bucket
*   `deleteFile` - delete a file
*   `getBucketLifeCycle` get the expiring date of a bucket
*   `getBucketSize` get the size (in Bytes) of files in a bucket
*   `getFile` - get all informations for a file
*   `getFilesInABucket` get an array of files in a bucket
*   `getPublicFileLink` - get the public link to download the file
*   `hasBucket` - check if a bucket exists
*   `hasFile` - check if a file exists
*   `uploadFile` - upload an object to a bucket

## Naming validation for buckets and objects

Please refer to the official AWS policy:

*   [Bucket naming restrictions and limitations](https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/BucketRestrictions.html)
*   [Object safe naming rules](https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/UsingMetadata.html)

The Client comes with two validators:
 
*    ```S3BucketNameValidator``` 
*    ```S3ObjectSafeNameValidator``` 
 
These two classes throws you an ```InvalidS3BucketNameException``` if the name is not compliant with the AWS rule conventions. 

Validators are invoked in Client's ```createBucketIfItDoesNotExist``` and ```uploadFile``` methods.

## Logging

In order to log Client calls, you can inject your logger. Please note that your logger MUST be PSR-3 compliant:

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
