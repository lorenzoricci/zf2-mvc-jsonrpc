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

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractRpcService
 *
 * Provides facilities to inject Service Locator and Event Manager
 *
 * @package ZF2MvcJsonRPC\Server
 */
abstract class AbstractRpcService implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
    /**
     * Contains the events
     *
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Contains the service locator
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Inject an EventManager instance
     *
     * @see \Zend\EventManager\EventManagerAwareInterface::setEventManager()
     * @param  EventManagerInterface $events
     * @return void
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
            __CLASS__,
            get_class($this)
        ));
        $this->events = $events;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @see \Zend\EventManager\EventsCapableInterface::getEventManager()
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

    /**
     * Set service locator
     *
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::setServiceLocator()
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::getServiceLocator()
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator() {
        return $this->serviceLocator;
    }
}