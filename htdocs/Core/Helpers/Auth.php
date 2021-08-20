<?php
/**
 * Copyright (C) 2021-2021  Rafael San José Tovar   <info@rsanjoseo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Alxarafe\Core\Helpers;

use Alxarafe\Core\Base\Singleton;
use Alxarafe\Core\Singletons\DebugTool;
use Alxarafe\Database\Engine;
use Alxarafe\Modules\Main\Controllers\Login;
use Alxarafe\Modules\Main\Models\Users;
use DebugBar\DebugBarException;

/**
 * Class Auth
 *
 * @package Alxarafe\Helpers
 */
class Auth extends Singleton
{

    /**
     * TODO: Undocumented
     */
    const COOKIE_EXPIRATION = 0;

    /**
     * TODO: Undocumented
     *
     * @var string|null
     */
    private $user = null;

    private $users;

    private $debug;

    /**
     * Auth constructor.
     */
    public function __construct()
    {
        $this->users = new Users();
        $this->getCookieUser();
        $this->debug = DebugTool::getInstance();
    }

    /**
     * TODO: Undocummented
     */
    private function getCookieUser()
    {
        if ($this->user === null) {
            if (isset($_COOKIE['user'])) {
                $this->user = $_COOKIE['user'];
            }
        }
    }

    /**
     * TODO: Undocummented
     * Esto no puede ser porque invoca a Login y carga el controlador.
     */
    public function login()
    {
        //        dump(debug_backtrace());
        new Login();
    }

    /**
     * @throws DebugBarException
     */
    public function logout()
    {
        $this->debug->addMessage('messages', 'Auth::Logout(): ' . ($this->user === null ? 'There was no identified user.' : 'User' . $this->user . ' has successfully logged out'));
        $this->user = null;
        $this->clearCookieUser();
    }

    /**
     * TODO: Undocummented
     */
    private function clearCookieUser()
    {
        setcookie('user', '');
        unset($_COOKIE['user']);
    }

    /**
     * TODO: Undocumented
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Try login for user and password.
     * Dolibarr uses serveral systems
     *
     * @param $user
     * @param $password
     *
     * @return bool
     * @throws DebugBarException
     * @see dol_hash in "htdocs/core/lib/security.lib.php"
     *
     */
    public function setUser($user, $password)
    {
        $username_field = 'login';  // Alxarafe use 'username', but Dolibarr use 'login'
        $password_field = 'pass_crypted';  // Alxarafe use 'password', but Dolibarr use 'pass_crypted'
        $encrypt_method = "password_hash"; // Alxarafe use 'md5', but Dolibarr use a function called dol_hash

        $_user = Engine::select("SELECT * FROM {$this->users->tableName} WHERE $username_field='$user';");
        if (count($_user) > 0 && password_verify($password, $_user[0][$password_field])) {
            $this->user = $user;
            setcookie('user', $user);
            $this->debug->addMessage('SQL', "$user autenticado");
        } else {
            $this->user = null;
            setcookie('user', '');
            unset($_COOKIE['user']);
            if (isset($_user[0])) {
                $this->debug->addMessage('SQL', "Comprobado {$encrypt_method}:" . $encrypt_method($password, PASSWORD_DEFAULT) . ', en fichero: ' . $_user[0][$password_field]);
            } else {
                $this->debug->addMessage('SQL', "Comprobado {$encrypt_method}:" . $encrypt_method($password, PASSWORD_DEFAULT) . ', en fichero no existe usuario ' . $user);
            }
        }
        return $this->user != null;
    }

    /**
     * TODO: Undocummented
     */
    private function setCookieUser()
    {
        setcookie('user', $this->user === null ? '' : $this->user, self::COOKIE_EXPIRATION);
    }
}