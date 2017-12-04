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

/**
 * The main Server that handles the requests
 */

namespace ZF2MvcJsonRPC\Server;

use Zend\Server\Reflection;
use Zend\Code\Reflection\DocBlockReflection;
use Zend\Json\Server\Server as JsonServer;

/**
 * Class Server
 * @package ZF2MvcJsonRPC\Server
 */
class Server extends JsonServer
{
    /**
     * Register a class with the server
     *
     * @param  string $class
     * @param  string $namespace Ignored
     * @param  mixed $argv Ignored
     * @return Server
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $reflection = Reflection::reflectClass($class, $argv, $namespace);

        $_methods = $reflection->getMethods();
        foreach($_methods as $k=>$m){
            $scanner    = new DocBlockReflection(($m->getDocComment()) ? : '/***/');

            $findTag = $scanner->getTags('rpcDiscoverable');
            if ( is_array($findTag) && count($findTag) ){
                $definition = $this->_buildSignature($m, $class);
                $this->_addMethodServiceMap($definition);
            }
        }
        return $this;
    }
}