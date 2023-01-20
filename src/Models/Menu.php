<?php
/**
 * Copyright (C) 2022-2023  Rafael San José Tovar   <info@rsanjoseo.com>
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

namespace Alxarafe\Models;

use Alxarafe\Core\Base\Table;
use Alxarafe\Database\Engine;

/**
 * Class Menu
 *
 * @package Modules\Main\Models
 */
class Menu extends Table
{

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct('menus', ['create' => true]);
    }

    public function getMenu(string $module)
    {
        return $this->getFromMenu($module);
    }

    private function getFromMenu(string $module, string $type = 'top')
    {
        $sql = "SELECT * FROM $this->tableName WHERE module='$module' AND type='$type' ORDER BY position";
        $data = Engine::select($sql);

        $result = [];
        foreach ($data as $dato) {
            $item = [];
            $item['text'] = $dato['titre'];
            $item['url'] = $dato['url'];
            $item['level'] = $dato['fk_leftmenu'] === null ? 1 : 2;
            $result[] = $item;
        }
        return $result;
    }

    public function getSubmenu(string $module)
    {
        return $this->getFromMenu($module, 'left');
    }

}
