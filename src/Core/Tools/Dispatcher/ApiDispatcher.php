<?php

/* Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Alxarafe\Tools\Dispatcher;

use Alxarafe\Base\Controller\ApiController;
use Alxarafe\Lib\Routes;
use Alxarafe\Tools\Debug;

class ApiDispatcher extends Dispatcher
{
    protected static function dieWithMessage($message)
    {
        Debug::message('ApiDispatcher error:');
        ApiController::badApiCall();
        die();
    }

    /**
     * Run the API call for the indicated module, if it exists.
     * Execution die with a json response.
     *
     * @param $route
     * @return void
     */
    public static function run($route)
    {
        $array = explode('/', $route);
        $module = $array[0];
        $controller = $array[1] ?? null;
        $method = $array[2] ?? null;

        $routes = Routes::getAllRoutes();
        $endpoint = $routes['Api'][$module][$controller] ?? null;
        if ($endpoint === null) {
            Debug::message("Dispatcher::runApi error: $route does not exists");
            ApiController::badApiCall();
        }

        Debug::message("Dispatcher::runApi executing $route ($endpoint)");
        $route_array = explode('|', $endpoint);
        $className = $route_array[0];
        $filename = $route_array[1];

        if (!file_exists($filename)) {
            Debug::message("Dispatcher::runApi error: $filename does not exists");
            ApiController::badApiCall();
        }

        require_once $filename;

        $controller = new $className();
        if ($controller === null) {
            Debug::message("Dispatcher::runApi error: $className not found");
            ApiController::badApiCall();
        }
    }
}
