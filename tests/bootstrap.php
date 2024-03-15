<?php

require __DIR__ . '/../repo/vendor/autoload.php';

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Slim\Exception\InvalidMethodException;

use Slim\Exception\NotFoundException;

$app = '';
class BaseApiWWWTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Use middleware when running application?
     *
     * @var bool
     */
    protected $withMiddleware = true;

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param array|object|null $requestData the request data
     * @return \Slim\Http\Response
     */
    public function runApp($requestMethod, $requestUri, $requestData = null)
    {
        global $app;
        // chdir( __DIR__ . '/../api.www_old');
        // Create a mock environment for testing with
        $environment = Environment::mock(
            [
                'REQUEST_METHOD' => $requestMethod,
                'REQUEST_URI' => $requestUri
            ]
        );

        // Set up a request object based on the environment
        $request = Request::createFromEnvironment($environment);

        // Add request data, if it exists
        if (isset($requestData)) {
            $request = $request->withParsedBody($requestData);
        }

        // Set up a response object
        $response = new Response();

        // Use the application settings
        $settings = require __DIR__ . '/../config/settings.php';

        // Instantiate the application
        $app = new App($settings);

        // Set up dependencies
        require __DIR__ . '/../api.www/src/dependencies.php';

        // Register middleware
        if ($this->withMiddleware) {
            require __DIR__ . '/../api.www/src/middleware.php';
        }

        // Register routes
        require __DIR__ . '/../api.www/src/routes.php';

        // $app->getContainer()->request = $request;
        // Process the application
        // $app->run(false);

        // Return the response
        // return $app->getContainter()->response;
        // $response = $app->getContainer()->response;

        // try {
            // $app->process($request, $response);
        // $response=$app->__invoke($request, $response);
        // } catch (InvalidMethodException $e) {
        //     $response = $app->processInvalidMethod($e->getRequest(), $response);
        // } finally {
        //     // not do something
        // }

        // print_r($response);
        // exit;
        // $objReflectClass = new ReflectionClass($app);
        // $method          = $objReflectClass->getMethod('processInvalidMethod');
        // $method->setAccessible(true);
        // $method->invoke($request, $response);
        // print_r($response);
        // exit;
        // print_r($method);
        // exit;
        // $response = call_user_func_array(array($method, 'processInvalidMethod'), [$request, $response]);
        // $response = $app->processInvalidMethod2($request, $response);
        
        // $app->getContainer->get('notFoundHandler')
        // ob_start();
        $app->getContainer()->request = $request;
        $notFoundHandler = $app->getContainer()->get('notFoundHandler');
        $response = $notFoundHandler($request, $response);
        // $data = ob_get_clean();
        return $response;
    }
}