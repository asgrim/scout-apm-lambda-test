# scout-apm-lambda-test

Example deployable application to AWS Lambda.

This is a small sample repository showing how Scout APM can be set up for a PHP function in AWS Lambda, using the 
[Bref](https://bref.sh/) project.

## Pre-requisites

 * You need an AWS account
 * You need [`serverless`](https://serverless.com/) framework:
   * `npm install -g serverless`
   * [create AWS access keys](https://bref.sh/docs/installation/aws-keys.html)
   * `serverless config credentials --provider aws --key <key> --secret <secret>`
 * Set up an SSM Parameter containing your [ScoutAPM Agent Key](https://scoutapm.com/settings)
   * Use the path `/scoutapm/bref-lambda-test/scout-apm-key` for the key 
   * Make sure the parameter is set up in the region you wish to deploy to!

## Demo setup

 * Clone this project to your development environment
 * `composer install`
 * Check over `serverless.yml`:
   * If you put the ScoutAPM Agent Key in a different path, correct this in the `provider.environment.SCOUT_KEY` value
   * If you want to use a different region than `eu-west-2`, change `provider.region`
 * Run `serverless deploy` (this can take a few minutes)
 * Click the link displayed after
 * You should see the log output from the logger displayed as JSON

If you wish to verify data is appearing in your dashboard, you would need to make the request last >2 seconds. To
simulate that, uncomment the `sleep(2);` in `index.php` and re-deploy.

Once you are finished, you can remove all resources created with `serverless remove`.

## To add Scout to YOUR Bref Function

### Use the base PHP library

Install the base Scout APM library for PHP with Composer using `composer require scoutapp/scout-apm-php`. If you don't
already have a PSR-3 compatible logger, run `composer require scoutapp/scout-apm-php monolog/monolog` instead.

Follow [the usual instructions](https://github.com/scoutapp/scout-apm-php#using-the-base-library-directly) to create an
instance of the Core Agent. Instead of providing the configuration from hard-coded values, you could use environment
variables powered by SSM parameters (see the example in this repository for the agent key). Make sure these are
defined in `serverless.yml` too.

Wrap your entire function in a call to `webTransaction`, `backgroundTransaction`, or `instrument`, like so:

```php
$agent = Agent::fromConfig(/* parameters go here... */);
$scout->connect();
$scout->webTransaction(
    'MyLambdaFucntion',
    static function () use ($logger): void {
        // Put all the code for your Lambda function here
    }
);
$scout->send();
```

 * [Example commit from this repository](https://github.com/asgrim/scout-apm-lambda-test/commit/952205b0d99ff808d7871ca2dd42fc0efdcccf10)

### Adding the `scoutapm` PHP extension

To get the additional functionality and instrumentation provided by [the `scoutapm` PHP extension](https://github.com/scoutapp/scout-apm-php-ext)
you can use `bref/extra-php-extensions` to add a layer. Support for Scout APM was added in 0.11.29 of this library, so
make sure you are using at least this.

First, install with Composer, `composer require bref/extra-php-extensions:^0.11.29`. In your `serverless.yml`, add
`./vendor/bref/extra-php-extensions` to the `plugins`.

Then, in your `layers` for your function(s), add the `${bref-extra:scoutapm-php-XX}` layer, where `XX` is the PHP
version you are using. The following are supported (at the time of writing) by `bref/extra-php-extensions`:

 * `${bref-extra:scoutapm-php-73}`
 * `${bref-extra:scoutapm-php-74}`
 * `${bref-extra:scoutapm-php-80}`
 * `${bref-extra:scoutapm-php-81}`

Re-deploy your application with `serverless deploy`.

 * [Example commit from this repository](https://github.com/asgrim/scout-apm-lambda-test/commit/331a97de00ec2b2d79124d0b78b987c27c82cfc5)
