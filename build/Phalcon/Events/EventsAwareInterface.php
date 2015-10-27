<?php
/**
 * Events Aware Interface
 *
*/
namespace Phalcon\Events;

/**
 * Phalcon\Events\EventsAwareInterface initializer
 *
 * This interface must for those classes that accept an EventsManager and dispatch events
 */
interface EventsAwareInterface
{
    /**
     * Sets the events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager);

    /**
     * Returns the internal event manager
     *
     * @return \Phalcon\Events\ManagerInterface
     */
    public function getEventsManager();
}
