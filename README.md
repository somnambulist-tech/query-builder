# Somnambulist Query Builder

[![GitHub Actions Build Status](https://img.shields.io/github/workflow/status/somnambulist-tech/query-builder/tests?logo=github)](https://github.com/somnambulist-tech/query-builder/actions?query=workflow%3Atests)
[![Issues](https://img.shields.io/github/issues/somnambulist-tech/query-builder?logo=github)](https://github.com/somnambulist-tech/query-builder/issues)
[![License](https://img.shields.io/github/license/somnambulist-tech/query-builder?logo=github)](https://github.com/somnambulist-tech/query-builder/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/somnambulist/query-builder?logo=php&logoColor=white)](https://packagist.org/packages/somnambulist/query-builder)
[![Current Version](https://img.shields.io/packagist/v/somnambulist/query-builder?logo=packagist&logoColor=white)](https://packagist.org/packages/somnambulist/query-builder)

An SQL query builder implementation for building SQL queries programmatically. Primarily focused
on `SELECT` queries, the query builder provides a core that can be extended with custom functionality
and per database dialects.

This library does not provide a driver implementation: it is a pure query builder / compiler to a
given dialect. A driver implementation is required such as DBAL, Laminas etc. Please note: the
query builder does not enforce any portability between database servers, a query built for one may
not function if run on another.

This query builder is derived from the excellent work done by the [Cake Software Foundation](https://github.com/cakephp/database).

## Requirements

 * PHP 8.1+

## Installation

Install using composer, or checkout / pull the files from github.com.

 * composer require somnambulist/query-builder

## Usage



### Tests

PHPUnit 9+ is used for testing. Run tests via `vendor/bin/phpunit`.
