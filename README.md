# Zend Framework 2 Json RPC controllers inside MVC

## Introduction

This module provide a layer for using Json RPC calls inside the MVC container without the need to use a separate entry point.

## Install

Simply include the module inside your application.config.php

```php
<?php
return array(
    'modules' => array(
        ...
        'ZF2MvcJsonRPC',
        ...
    ),
);
```		

## Using

If you want the features of this module you must use the magic of service manager/locator

An example is hereby produced:


```php
return array(
    'router' => array(
        'routes' => array(
        
            'supersayan' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/my-supersayan-service',
                    'defaults' => array(
                        'controller' => 'Application\Rpc\MySupersayanService',
                    ),
                ),
            ),
            
        ),
    ),
    
    'controllers' => array(
        'invokables' => array(
            'Application\Rpc\MySupersayanService'   => 'ZF2MvcJsonRPC\Server\RpcController',
        ),
    ),

    'service_manager' => array(
        'invokables' => array(
            'RPC_Application_Rpc_MySupersayanService' => 'Application\Rpc\MySupersayanService',
        ),

        'aliases' => array(
            'Application\Rpc\MySupersayanService'   => 'RPC_Application_Rpc_MySupersayanService',
        ),

    ),
    
);
```

When declaring the controller of an action, the controller must be mapped to the internal RpcController as an invokable.

This is how to map an action; we are mapping the action named **supersayan** to a route exposed to web as **/my-supersayan-service** and 
the resulting controller is **Application\Rpc\MySupersayanService**
```php
'supersayan' => array(
    'type'    => 'Literal',
    'options' => array(
        'route'    => '/my-supersayan-service',
        'defaults' => array(
            'controller' => 'Application\Rpc\MySupersayanService',
        ),
    ),
),
```

The controller is mapped to the internal Rpc controller that automagically will expose the methods
```php
    'controllers' => array(
        'invokables' => array(
            'Application\Rpc\MySupersayanService'   => 'ZF2MvcJsonRPC\Server\RpcController',
        ),
    ),
```

Now you must tell the system which one is the class that will be called when the route is requested. 
```php
    'service_manager' => array(
        'invokables' => array(
            'RPC_Application_Rpc_MySupersayanService' => 'Application\Rpc\MySupersayanService',
        ),

        'aliases' => array(
            'Application\Rpc\MySupersayanService'   => 'RPC_Application_Rpc_MySupersayanService',
        ),

    ),
```

To expose a method you must mark it as **discoverable by rpc** using the annotation **@rpcDiscoverable** in the docComment.
Here is an example:
```php
<?php
namespace Application\Rpc;

use ZF2MvcJsonRPC\Server\AbstractRpcService as AbstractRpcService;

class MySupersayanService extends AbstractRpcService
{

    /**
     * The super sayan method
     *
     * @rpcDiscoverable
     *
     * @param $enemy string
     * @return THE_CLASS_MODEL_YOU_WANT_TO_SEND_BACK
     * @throws \Exception
     */
    public function fight($enemy) {
        throws new \Exception("Method must be implemented!");
    }
}
```

## From the client perspective

This is how to call the methods from the user/client perspective. Maybe an example using angular-js will be provided in the future. 

### JSON RPC Request

The method must be called from an RPC client using a json object. This is an example of the request:
```javascript
{
    "jsonrpc":"2.0",
    "id":1,
    "method":"fight",
    "params":{
        "enemy":"goku"
        }
    }
}
```

### JSON RPC Response

The result that will be produced by the controller is a json object. 
```javascript
{
    "jsonrpc": "2.0",
    "id": "1",
    "result": {
        "win": true
    }
}
```

If an error is thrown by the system this is the returning json
```javascript
{
    "jsonrpc": "2.0",
    "id": "1",
    "error": {
        "code": 666,
        "message": "You are a loser",
        "data": {
          "debug": "You are a loser and nothing can be done to avoid this problem..."
        }
    }
}
```

Ask giampaolo and he will explains you everything about the code!