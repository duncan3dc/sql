# sql
A simple database abstraction layer for PHP, with an on disk caching facility

Full documentation is available at http://duncan3dc.github.io/sql/  
PHPDoc API documentation is also available at [http://duncan3dc.github.io/sql/api/](http://duncan3dc.github.io/sql/api/namespaces/duncan3dc.Laravel.html)  

[![Latest Stable Version](https://poser.pugx.org/duncan3dc/sql/version.svg)](https://packagist.org/packages/duncan3dc/sql)
[![Build Status](https://travis-ci.org/duncan3dc/sql.svg?branch=master)](https://travis-ci.org/duncan3dc/sql)
[![Coverage Status](https://coveralls.io/repos/github/duncan3dc/sql/badge.svg)](https://coveralls.io/github/duncan3dc/sql)


## Installation

The recommended method of installing this library is via [Composer](//getcomposer.org/).

Run the following command from your project root:

```bash
$ composer require duncan3dc/sql
```


## Getting Started

```php
use duncan3dc\Sql\Sql;
use duncan3dc\Sql\Drivers\Mysql\Server;

require __DIR__ . "/vendor/autoload.php";

$sql = new Sql(new Server($hostname, $username, $password));

$row = $sql->select("table", [
    "field1"    =>  "value1",
]);
print_r($row);
```

_Read more at http://duncan3dc.github.io/sql/_  


## Changelog
A [Changelog](CHANGELOG.md) has been available since the beginning of time


## Where to get help
Found a bug? Got a question? Just not sure how something works?  
Please [create an issue](//github.com/duncan3dc/sql/issues) and I'll do my best to help out.  
Alternatively you can catch me on [Twitter](https://twitter.com/duncan3dc)
