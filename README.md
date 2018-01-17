# SimpleSAMLphp Mongo Module

[![Travis build status](https://img.shields.io/travis/prolificinteractive/PROJECT_NAME.svg?style=flat-square)](https://travis-ci.org/prolificinteractive/PROJECT_NAME)

The mongo module is an implementation of a SimpleSAMLphp (SSP) data store.   

## Features

- Can be used for backend storage of sessions in MongoDB
- Includes support for replica sets

## Requirements

PHP 5.3 through 5.6 

[SimpleSAMLphp](https://simplesamlphp.org/)

[MongoDB](https://www.mongodb.com/)

[Mongo PHP extension](http://php.net/manual/en/book.mongo.php)

## Installation

If your project manages SSP with [Composer](https://getcomposer.org/) run:
```
php composer.phar require prolificinteractive/simplesamlphp-module-mongo
```
This command will add `prolificinteractive/simplesamlphp-module-mongo` to your projects' composer.json file and install the module 
into SSP's `modules` directory, which relative to your project's root directory is conventionally `vendor/simplesamlphp/simplesamlphp/modules`.

## Usage

Set the `store.type` option in your SSP config file to `mongo:Store`.

Provide your MongoDB connection information to the module by copying the file provided in the `config-templates` directory into SSP's config directory, and setting the following environment variables:
```
DB_MONGODB_HOST
DB_MONGODB_PORT
DB_MONGODB_USERNAME
DB_MONGODB_PASSWORD
DB_MONGODB_DATABASE
``` 

If your connecting to a replica set, you'll need to set the following environment variables below as well:
```
DB_DEFAULT_CONNECTION   # Must contain the substring "_replica"
DB_MONGODB_REPLICASET
DB_MONGODB_READ_PREFERENCE
```
See the [Mongo extension PHP Manual](http://php.net/manual/en/mongo.manual.php) for more information about appropriate values for `DB_MONGODB_REPLICASET` and `DB_MONGODB_READ_PREFERENCE`. 

Finally, you can enable the module by creating an empty file name `enable` in the `vendor/simplesamlphp/simplesamlphp/modules/mongo` directory.

**Note:** This module stores PHP session data in the `session` collection. 

## Contributing to SimpleSAMLphp Mongo Module

To report a bug or enhancement request, feel free to file an issue under the respective heading.

If you wish to contribute to the project, fork this repo and submit a pull request.

## License

![prolific](https://s3.amazonaws.com/prolificsitestaging/logos/Prolific_Logo_Full_Color.png)

Copyright (c) 2017 Prolific Interactive

SimpleSAMLphp Mongo Module is maintained and sponsored by Prolific Interactive. It may be redistributed under the terms specified in the [LICENSE] file.

[LICENSE]: ./LICENSE