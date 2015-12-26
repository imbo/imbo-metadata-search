<?php

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use ImboClient\ImboClient;
use Assert\Assertion;
use Guzzle\Plugin\History\HistoryPlugin;

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
     * Imbo client used to make requests against the httpd
     *
     * @var ImboClient\ImboClient
     */
    protected $imbo;

    /**
     * The guzzle request and response history
     *
     * @var Guzzle\Plugin\History\HistoryPlugin
     */
    protected $history;

    /**
     * Query params used when doing non Imbo client requests
     *
     * @var array
     */
    protected $queryParams = [];

    /**
     * Headers for the request
     *
     * @var array
     */
    protected $requestHeaders = [];

    /**
     * Class constructor
     *
     * @param array $parameters Context parameters
     */
    public function __construct($url, $documentRoot, $router, $httpdLog, $timeout) {
        $this->params = [
            'url' => $url,
            'documentRoot' => $documentRoot,
            'router' => $router,
            'httpdLog' => $httpdLog,
            'timeout' => $timeout
        ];

        // Prepare history plugin for capturing requests and responses
        $this->history = new HistoryPlugin();

        // Prepare clients
        $this->createClient();
    }

    /**
     * Returns a list of HTTP verbs that we need to do an override of in order
     * to bypass limitations in the built-in PHP HTTP server.
     *
     * The returned list contains the verb to use override for, and what verb
     * to use when overriding. For instance POST could be used when we want to
     * perform a SEARCH request as a payload is expected while GET could be used
     * if we want to test something using the LINK method.
     */
    private function getOverrideVerbs() {
        return [
            'SEARCH' => 'POST'
        ];
    }

    /**
     * Create a new HTTP client
     */
    private function createClient() {
        $this->imbo = ImboClient::factory([
            'serverUrls' => [$this->params['url']],
            'publicKey' => 'publickey',
            'privateKey' => 'privatekey',
            'user' => 'user',
        ]);

        $eventDispatcher = $this->imbo->getEventDispatcher();
        $eventDispatcher->addSubscriber($this->history);

        $defaultHeaders = [
            'X-Test-Session-Id' => self::$testSessionId,
        ];

        $this->imbo->setDefaultHeaders($defaultHeaders);
    }

    /**
     * Start up the built in httpd in php-5.4
     *
     * @BeforeSuite
     */
    public static function setUp(BeforeSuiteScope $scope) {
        $contexts = $scope->getSuite()->getSettings()['contexts'];

        $params = $contexts[0]['FeatureContext'];

        $url = parse_url($params['url']);
        $port = !empty($url['port']) ? $url['port'] : 80;

        if (self::canConnectToHttpd($url['host'], $port)) {
            throw new RuntimeException('Something is already running on ' . $params['url'] . '. Aborting tests.');
        }

        // Empty httpd log before starting new server
        if (is_writeable($params['httpdLog'])) {
            file_put_contents($params['httpdLog'], '');
        }

        $pid = self::startBuiltInHttpd(
            $url['host'],
            $port,
            $params['documentRoot'],
            $params['router'],
            $params['httpdLog']
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
     * @param string $router The web server router
     * @param string $httpdLog Location of httpd log
     * @return int Returns the PID of the httpd
     * @throws RuntimeException
     */
    private static function startBuiltInHttpd($host, $port, $documentRoot, $router, $httpdLog) {
        $command = sprintf('php -S %s:%d -t %s %s > %s 2>&1 & echo $!',
                            $host,
                            $port,
                            $documentRoot,
                            $router,
                            $httpdLog);

        $output = [];
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

    public function rawRequest($path, $method = 'GET', $params = [], $body = null) {
        if (empty($this->requestHeaders['Accept'])) {
            $this->requestHeaders['Accept'] = 'application/json';
        }

        // Explicitly set the public key header
        $this->requestHeaders['X-Imbo-PublicKey'] = $this->imbo->getConfig('publicKey');

        $overrideMethod = false;

        // Override method
        if (array_key_exists($method, $this->getOverrideVerbs())) {
            $overrideMethod = $method;
            $method = $this->getOverrideVerbs()[$method];
        }

        $request = $this->imbo->createRequest($method, $path, $this->requestHeaders, $body);

        // Set override method header
        if ($overrideMethod) {
            $request->setHeader('X-Http-Method-Override', $overrideMethod);
        }

        // Add query params
        $request->getQuery()->merge($params);

        // Send request
        $request->send();

        // Create a new client instance to get rid of state
        $this->createClient();
    }

    /**
     * Get the response to the last request made by the Guzzle client
     *
     * @return Response
     */
    protected function getLastResponse() {
        $lastRequest = $this->history->getLastRequest();

        if (!$lastRequest) {
            return null;
        }

        return $lastRequest->getResponse();
    }

    /**
     * @Given /^I use "([^"]*)" and "([^"]*)" for public and private keys$/
     */
    public function setClientAuth($publicKey, $privateKey) {
        $this->imbo->setConfig([
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
            'user' => 'user',
        ]);
    }

    /**
     * @Then /^I should get a response with "([^"]*)"$/
     */
    public function assertResponseStatus($status) {
        $response = $this->getLastResponse();

        $actual = $response->getStatusCode() . ' ' . $response->getReasonPhrase();

        Assertion::same($actual, $status);
    }

    /**
     * @Given I set the :param query param to :value
     */
    public function setQueryParam($param, $value)
    {
        $this->queryParams[$param] = $value;
    }
}