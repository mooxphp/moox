<?php

/**
 * Event dispatcher
 */

namespace WpOrg\Requests;

/**
 * Event dispatcher
 */
interface HookManager
{
    /**
     * Register a callback for a hook
     *
     * @param  string  $hook  Hook name
     * @param  callable  $callback  Function/method to call on event
     * @param  int  $priority  Priority number. <0 is executed earlier, >0 is executed later
     */
    public function register($hook, $callback, $priority = 0);

    /**
     * Dispatch a message
     *
     * @param  string  $hook  Hook name
     * @param  array  $parameters  Parameters to pass to callbacks
     * @return bool Successfulness
     */
    public function dispatch($hook, $parameters = []);
}
