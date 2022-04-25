<?php
declare(strict_types=1);

use Scoutapm\Agent;
use Scoutapm\Config;
use Scoutapm\Config\ConfigKey;

require_once __DIR__ . '/vendor/autoload.php';

$handler = new \Monolog\Handler\TestHandler();

$logger = new \Monolog\Logger('scout-logs');
$logger->pushHandler($handler);

try {
    $scout = Agent::fromConfig(
        Config::fromArray([
            ConfigKey::APPLICATION_NAME => 'Bref Lambda Test',
            /**
             * Note - the agent key comes from the environment variable `SCOUT_KEY` here.
             * This is wired in `serverless.yml` to come from `${ssm:/scoutapm/bref-lambda-test/scout-apm-key}` so
             * this function expects that SSM parameter is preconfigured IN THE REGION you are deploying to.
             */
            ConfigKey::MONITORING_ENABLED => true,
        ]),
        $logger
    );
    $scout->connect();

    $scout->webTransaction(
        'LambdaFunction',
        static function () use ($logger): void {
            $logger->debug('Lambda function started');

            // Comment this to avoid making the function take time, uncomment it for testing only
//            sleep(2);

            $logger->debug('Lambda function ended');
        }
    );

    $scout->send();
} catch (\Throwable $unhandled) {
    $logger->critical(
        sprintf(
            'Unhandled Exception: %s',
            $unhandled->getMessage()
        ),
        [
            'exception' => $unhandled,
        ]
    );
}


header('Content-type: application/json');
echo json_encode($handler->getRecords(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
