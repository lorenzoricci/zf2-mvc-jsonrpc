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

/**
 * Class GenericResponse
 * @package ZF2MvcJsonRPC\Server
 */
class GenericResponse implements IResponse
{
    /**
     * Holds the data that will be sent back to client
     *
     * @var null
     */
    public $data = null;

    /**
     * Holds the response message
     *
     * @var string
     */
    public $message;
    
    /**
     * Set data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get data
     *
     * @param mixed $data
     * @return RpcMessage
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return RpcMessage
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Return the formatted response
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            "data"    => $this->data,
            "message"   => $this->message,
        );
    }

}