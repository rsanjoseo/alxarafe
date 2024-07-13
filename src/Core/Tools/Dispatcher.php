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

namespace Alxarafe\Tools;

use Alxarafe\Lib\Functions;
use Alxarafe\Lib\Trans;

class Dispatcher
{
    /**
     * Run the controller for the indicated module, if it exists.
     * Returns true if it can be executed.
     *
     * @param string $module
     * @param string $controller
     * @param array $alternative_routes
     *
     * @return bool
     */
    public static function run(string $module, string $controller, array $alternative_routes = []): bool
    {
        /**
         * Adding core module path
         */
        $routes = array_merge($alternative_routes, [
            'CoreModules' => 'vendor/rsanjoseo/alxarafe/src/Modules/',
        ]);
        $controller .= 'Controller';
        foreach ($routes as $class => $route) {
            if (static::processFolder($class, $route, $module, $controller)) {
                Debug::message("Dispatcher::process(): Ok");
                return true;
            }
        }
        Debug::message("Dispatcher::fail(): $module:$controller.");
        return false;
    }

    /**
     * Process modern application controller paths.
     *
     * @param string $class
     * @param string $route
     * @param string $module
     * @param string $controller
     * @return bool
     */
    private static function processFolder(string $class, string $route, string $module, string $controller): bool
    {
        /**
         * Define BASE_PATH if it does not exist.
         * It's usually created in main index.php.
         * It's the full path to the public folder.
         */
        Functions::defineIfNotDefined('ALX_PATH', realpath(__DIR__ . '/../../..'));
        Functions::defineIfNotDefined('APP_PATH', realpath(constant('ALX_PATH') . '/../../..'));
        Functions::defineIfNotDefined('BASE_PATH', constant('APP_PATH') . '/public');
        Functions::defineIfNotDefined('BASE_URL', Functions::getUrl());

        /**
         * Defines the full path ($realpath) to the modules folder ($route).
         */
        $realpath = realpath(constant('APP_PATH') . '/' . $route);

        /**
         * Adds the module to the path ($basepath), if it's a module.
         */
        $basepath = $realpath;
        if (!empty($module)) {
            $basepath = $realpath . '/' . $module;
        }

        /**
         * Defines full classname and filename
         */
        $className = $class . '\\' . $module . '\\Controller\\' . $controller;
        $filename = $basepath . '/Controller/' . $controller . '.php';

        Debug::message('Filename: ' . $filename);
        Debug::message('Class: ' . $className);
        if (!file_exists($filename)) {
            return false;
        }

        $controller = new $className();
        if ($controller === null) {
            return false;
        }

        /**
         * If the class exists and is successfully instantiated, the module blade templates folder
         * is added, if they exist.
         */
        if (method_exists($controller, 'setTemplatesPath')) {
            $templates_path = $basepath . '/Templates';
            Debug::message('Templates: ' . $templates_path);
            $controller->setTemplatesPath($templates_path);
        }

        /**
         * Runs the index method to launch the controller.
         */
        $controller->index();

        return true;
    }
}
