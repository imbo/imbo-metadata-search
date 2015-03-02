<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Imbo\Application as Imbo;

/**
 * Defines REST context for behat tests.
 */
class RESTContext implements Context
{
    /**
     * The current test session id
     *
     * @var string
     */
    private static $testSessionId;

    /**
     * Start up the built in httpd in php-5.4
     *
     * @BeforeSuite
     */
    public static function setUp(BeforeSuiteScope $scope) {
        $scopeSettings = $scope->getSuite()->getSettings();
        $params = $scopeSettings['parameters'];

        $url = parse_url($params['url']);
        $port = !empty($url['port']) ? $url['port'] : 80;

        if (self::canConnectToHttpd($url['host'], $port)) {
            throw new RuntimeException('Something is already running on ' . $params['url'] . '. Aborting tests.');
        }

        $pid = self::startBuiltInHttpd(
            $url['host'],
            $port,
            $params['documentRoot'],
            $params['router']
        );

        if (!$pid) {
            // Could not start the httpd for some reason
            throw new RuntimeException('Could not start the web server');
        }

        // Try to connect
        $start = microtime(true);
        $connected = false;

        while (microtime(true) - $start <= (int) $params['timeout']) {
            if (self::canConnectToHttpd($url['host'], $port)) {
                $connected = true;
                break;
            }
        }

        if (!$connected) {
            throw new RuntimeException(
                sprintf(
                    'Could not connect to the web server within the given timeframe (%d second(s))',
                    $params['timeout']
                )
            );
        }

        // Register a shutdown function that will automatically shut down the httpd
        register_shutdown_function(function() use ($pid) {
            exec('kill ' . $pid);
        });

        self::$testSessionId = uniqid('', true);
    }

    /**
     * Start the built in httpd in php-5.4
     *
     * @param string $host The hostname to use
     * @param int $port The port to use
     * @param string $documentRoot The document root
     * @param string $router Path to an optional router
     * @return int Returns the PID of the httpd
     * @throws RuntimeException
     */
    private static function startBuiltInHttpd($host, $port, $documentRoot, $router = null) {
        $command = sprintf('php -S %s:%d -t %s %s >/dev/null 2>&1 & echo $!',
                            $host,
                            $port,
                            $documentRoot,
                            $router);

        $output = array();
        exec($command, $output);

        return (int) $output[0];
    }

    /**
     * See if we have an httpd we can connect to
     *
     * @param string $host The hostname to connect to
     * @param int $port The port to use
     * @return boolean
     */
    private static function canConnectToHttpd($host, $port) {
        set_error_handler(function() { return true; });
        $sp = fsockopen($host, $port);
        restore_error_handler();

        if ($sp === false) {
            return false;
        }

        fclose($sp);

        return true;
    }
}