# NYPL Docs Service

[![Build Status](https://travis-ci.org/NYPL/docsservice.svg?branch=development)](https://travis-ci.org/NYPL/docsservice)
[![Coverage Status](https://coveralls.io/repos/github/NYPL/docsservice/badge.svg?branch=development)](https://coveralls.io/github/NYPL/docsservice?branch=development)

This app serves the following:
 * `GET /api/v0.1/docs` : Builds a combined Swagger doc from the Swagger partials configured in `DOCS_URLS`, writes the result to S3, and serves it.
 * `GET /docs/doc` : Serves the Swagger partial for this service (i.e. documents `/api/v0.1/docs`)

This package is intended to be used as an AWS Lambda Node.js/PHP Microservice to gather Swagger specifications from various URLs and combine them into a single Swagger specification.

This package uses the [NYPL PHP Microservice Starter](https://github.com/NYPL/php-microservice-starter) and adheres to [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/), and [PSR-4](http://www.php-fig.org/psr/psr-4/) (using the [Composer](https://getcomposer.org/) autoloader).

## Requirements

* Node.js >= 14
* PHP >=7.0
  * [pdo_pdgsql](http://php.net/manual/en/ref.pdo-pgsql.php)

Homebrew is highly recommended for PHP:
  * `brew install php71`
  * `brew install php71-pdo-pgsql`

## Installation

1. Clone the repo.
2. Install required dependencies.
   * Run `nvm use; npm install` to install Node.js packages.
   * Run `composer install` to install PHP packages.

## Configuration

Various files are used to configure and deploy the Lambda.

### .env

`.env` is used for two purposes:

1. By `node-lambda` for deploying to and configuring Lambda in *all* environments.
   * You should use this file to configure the common settings for the Lambda (e.g. timeout, Node version).
2. To set local environment variables so the Lambda can be run and tested in a local environment.
   These parameters are ultimately set by the [var environment files](#var_environment) when the Lambda is deployed.

### package.json

Configures `npm run` commands for each environment for deployment and testing.

### config/var_app

Configures environment variables common to *all* environments.

### config/var_*environment*.env

Configures environment variables specific to each environment.

### config/event_sources_*environment*

Configures Lambda event sources (triggers) specific to each environment. An empty object, but necessary

### Deployment config discrepancies

In general we would like the `DOCS_URLS` config to be equal across deployments. In practice that's not possible because docs endpoints aren't available for all apps in all environments. Some known differences:

**In development**, we can not use the following docs endpoints because they either don't exist or are broken:
 - /docs/check-in-cards
 - /docs/checkin-requests-sync
 - /docs/checkout-requests-sync
 - /docs/discovery
 - /docs/is-research
 - /docs/mylibrarynyc
 - /docs/patrons-validations
 - /docs/refile-requests
 - /docs/sync-item-metadata-to-scsb

## Usage

### Process a Lambda Event

To use `node-lambda` to process the sample API Gateway event in `event.json`, run:

~~~~
npm run test-docs-request
~~~~

### Run as a Web Server

To use the PHP internal web server, run:

~~~~
php -S localhost:8888 -t . index.php
~~~~

You can then make a request to the Lambda: `http://localhost:8888/api/v0.1/docs`.

## Deployment

Travis is set up to automatically deploy to the appropriate environment for development, qa, and production (main branch)

To deploy to an environment by hand, run the corresponding command:

~~~~
npm run deploy-[development|qa|production]
~~~~

## Contributing

This repo uses the [Development-QA-Main Git Workflow](https://github.com/NYPL/engineering-general/blob/master/standards/git-workflow.md#development-qa-main)
