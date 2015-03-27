<?php

/**
 * Router for the built in httpd in php-5.4. Route everything through index.php. When ran from the
 * base project directory, the command looks like this:
 *
 * php -S localhost:8888 -t tests/behat/imbo-docroot tests/behat/router.php
 */
// Hack to bypass limited support for non-standard HTTP verbs in the built-in PHP HTTP server
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    // Set request method
    $_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);

    // Unset the header
    unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}

return false;