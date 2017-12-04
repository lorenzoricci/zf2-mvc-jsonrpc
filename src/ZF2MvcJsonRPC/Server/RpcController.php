<?php
/**
 * This file is part of Zend Framework 2 MVC JsonRPC (later ZF2MVCJsonRPC).
 *
 * ZF2MVCJsonRPC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ZF2MVCJsonRPC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with ZF2MVCJsonRPC.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      https://github.com/lorenzoricci/zf2-mvc-jsonrpc source repository
 * @author    Lorenzo Ricci
 */

namespace ZF2MvcJsonRPC\Server;

use Zend\Mvc\Controller\AbstractController as MvcController;
use Zend\Http\Request as HttpRequest;
use Zend\Json\Json;
use Zend\Mvc\Exception;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;

use Zend\View\Model\JsonModel;
use Zend\Log\Logger as Logger;

use ZF2MvcJsonRPC\Server\Server as JsonRpcServer;
/**
 * Abstract JSON-RPC controller
 *
 * @package ZF2MvcJsonRPC\Server
 */
class RpcController extends MvcController
{
    const CONTENT_TYPE_JSON = 'json';

    /**
     * Prefix the events with the name of this class
     *
     * @var string
     */
    protected $eventIdentifier = __CLASS__;

    /**
     * Contains the allowed return types
     *
     * @var array
     */
    protected $contentTypes = array(
        self::CONTENT_TYPE_JSON => array(
            'application/json'
        )
    );

    /**
     * Name of request or query parameter containing identifier
     *
     * @var string
     */
    protected $identifierName = 'id';

    /**
     * @var int From Zend\Json\Json
     */
    protected $jsonDecodeType = Json::TYPE_ARRAY;

    /**
     * Map of custom HTTP methods and their handlers
     *
     * @var array
     */
    protected $customHttpMethodsMap = array();

    /**
     * Set the route match/query parameter name containing the identifier
     *
     * @param  string $name
     * @return self
     */
    public function setIdentifierName($name)
    {
        $this->identifierName = (string) $name;
        return $this;
    }

    /**
     * Retrieve the route match/query parameter name containing the identifier
     *
     * @return string
     */
    public function getIdentifierName()
    {
        return $this->identifierName;
    }

    /**
     * Basic functionality for when a page is not available
     *
     * @return array
     */
    public function notFoundAction()
    {
        $this->response->setStatusCode(404);

        return array(
            'content' => 'Page not found'
        );
    }

    /**
     * A listing method currently unused
     *
     * @return null
     */
    public function listMethodsAction()
    {
    }


    /**
     * Dispatch a request
     *
     * If the route match includes an "action" key, then this acts basically like
     * a standard action controller. Otherwise, it introspects the HTTP method
     * to determine how to handle the request, and which method to delegate to.
     *
     * @events dispatch.pre, dispatch.post
     * @param  Request $request
     * @param  null|Response $response
     * @return mixed|Response
     * @throws Exception\InvalidArgumentException
     */
    public final function dispatch(Request $request, Response $response = null)
    {
        if (! $request instanceof HttpRequest) {
            throw new Exception\InvalidArgumentException(
                'Expected an HTTP request');
        }

        return parent::dispatch($request, $response);
    }

    /**
     * Handle the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException if no route matches in event or invalid HTTP method
     */
    public final function onDispatch(MvcEvent $e)
    {
        /* @var $routeMatch \Zend\Mvc\Router\Http\RouteMatch */
        $routeMatch = $e->getRouteMatch();
        if (! $routeMatch) {
            throw new Exception\DomainException(
                'Missing route matches; unsure how to retrieve action');
        }

        /* @var $sm \Zend\ServiceManager\ServiceManager */
        $sm = $this->getServiceLocator();

//        $logger = $sm->get("logger");

        $request = $e->getRequest();
        $e->getViewModel()->setTerminal(true);

        /*
         * Which one is correct from those two classes?
         * This is a true mystery
         * \Zend\Http\Response
         * \Zend\Stdlib\ResponseInterface
         */
        /* @var $response \Zend\Http\Response */
        $response = $e->getResponse();

        $server = new JsonRpcServer();

        $server->setReturnResponse(true);

        $className = $routeMatch->getParam('controller');

        $rpcModel = null;

        if ( $sm->has( $className ) ){
            $rpcModel = $sm->get($className);
        } else {
            $rpcModel = new $className;
        }

        $server->setClass($rpcModel);

        switch ($request->getMethod()) {
            case 'GET':
            case 'OPTIONS':
                $uri = $this->getRequest()->getUri();
                $server->setTarget($uri)
                    ->setEnvelope(\Zend\Json\Server\Smd::ENV_JSONRPC_2);

                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');

                /* @var $serviceMap \Zend\Json\Server\Smd */
                $serviceMap = $server->getServiceMap(); //

                // Grab the SMD (Service Mapping Description)
                $viewModel =  new JsonModel($serviceMap->toArray());
                $return = $viewModel;
                break;

            case 'POST':
                /* @var $jsonRpcResponse \Zend\Json\Server\Response\Http */
                $jsonRpcResponse = $server->handle();

                $viewModel =  new JsonModel();
                $viewModel->setVariable('jsonrpc', '2.0');
                $viewModel->setVariable('id', $jsonRpcResponse->getId());


                if ( $jsonRpcResponse->isError() ) {
                    $this->log(Logger::ERR,
                        sprintf("Error unrecoverable [Code:%s]: %s", $jsonRpcResponse->getError()->getCode(), $jsonRpcResponse->getError()->getMessage() )
                        );

                    $this->log(Logger::INFO,
                        sprintf("Error details: %s", $jsonRpcResponse->getError()->getData() )
                        );

                    $errorValues = array(
                        'code' => $jsonRpcResponse->getError()->getCode(),
                        'message' => $jsonRpcResponse->getError()->getMessage()
                    );

                    if ( defined('APPLICATION_ENV') && getenv('APPLICATION_ENV') != "production" ){
                        $errorValues['data'] = array(
                            'trace' => $jsonRpcResponse->getError()->getData()
                        );
                    }

                    $viewModel->setVariable("error", $errorValues);
                    $response->setStatusCode(206);
                } elseif ( $jsonRpcResponse->getResult() instanceof IResponse ) {
                    //print_r($jsonRpcResponse->getResult());die();
                    /* @var $returnValues \Application\JsonRpcServer\IResponse */
                    $returnValues = $jsonRpcResponse->getResult();
                    $this->log(Logger::DEBUG,
                        sprintf("Returning IResponse instance from call. Values: [%s]", serialize($returnValues->toArray()))
                        );
                    $viewModel->setVariable("result", $returnValues->toArray());
                    $response->setStatusCode(200);
                } elseif (  is_array($jsonRpcResponse->getResult()) ) {
                    $this->log(Logger::DEBUG,
                        sprintf("Returning array from call. Values: [%s]", serialize($jsonRpcResponse->getResult()))
                        );
                    //print_r($jsonRpcResponse->getResult());die();
                    $viewModel->setVariable("result", $jsonRpcResponse->getResult());
                    $response->setStatusCode(200);
                } else {
                    //print_r($jsonRpcResponse->getResult());die();
                    $this->log(Logger::ERR,
                        sprintf("Something unknown is return from server [%s]", serialize($jsonRpcResponse->getResult()) )
                        );
                    $errorValues = array(
                        'code' => 666,
                        'message' => 'Un unknown reply was return after server handling...',
                    );
                    $viewModel->setVariable("error", $errorValues);
                }

                //var_dump($viewModel);die();
                $return = $viewModel;

                break;
            default:
                $response = $e->getResponse();
                $response->setStatusCode(405);
                $response->getHeaders()->addHeaderLine('Allow', 'GET,OPTIONS,POST');
                return $response;

                break;
        }

        $e->setResult($return);
    }

    /**
     * Log a message
     *
     * Log a message if a service named logger is declared in the
     * service locator and implements \Zend\Log\Logger
     *
     * @param  int $priority
     * @param  string $message
     */
    private function log($priority, $message){
        /* @var $sm \Zend\ServiceManager\ServiceManager */
        $sm = $this->getServiceLocator();

        if ( $sm->has("logger") ){
            $sm->get("logger")->log($priority, $message);
        }
    }
}