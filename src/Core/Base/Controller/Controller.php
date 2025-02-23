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

namespace Alxarafe\Base\Controller;

use Alxarafe\Base\Controller\Trait\DbTrait;
use Alxarafe\Lib\Auth;
use Alxarafe\Lib\Functions;
use CoreModules\Admin\Controller\AuthController;
use CoreModules\Admin\Controller\ConfigController;

/**
 * Class Controller. Controller is the general purpose controller and requires the user to be authenticated.
 *
 * @package Alxarafe\Base
 */
abstract class Controller extends ViewController
{
    use DbTrait;

    /**
     * Name of the user
     *
     * @var string
     */
    public $username;

    /**
     * Controller constructor.
     *
     * @throws \DebugBar\DebugBarException
     */
    public function __construct()
    {
        parent::__construct();

        if (!isset($this->config->db) || !static::connectDb($this->config->db)) {
            Functions::httpRedirect(ConfigController::url());
        }

        if (!Auth::isLogged() && static::class !== 'CoreModules\Admin\Controller\AuthController') {
            Functions::httpRedirect(AuthController::url());
        }

        $this->username = Auth::$user->name ?? null;
    }
}
