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

namespace Alxarafe\Modules\Main\Views;

use Alxarafe\Core\Base\View;
use Alxarafe\Core\Singletons\TemplateRender;

/**
 * Class Login
 *
 * @package Alxarafe\Views
 */
class LoginView extends View
{
    /**
     * TODO: Undocummented
     */
    public function addCss()
    {
        parent::addCss();
        //        $this->addToVar('cssCode', $this->addResource('/css/alxarafe', 'css'));
        //        $this->addToVar('cssCode', $this->addResource('/css/jquery-ui', 'css'));
        //        $this->addToVar('cssCode', $this->addResource('/css/jquery.jnotify-alt.min', 'css'));
        //        $this->addToVar('cssCode', $this->addResource('/css/select2', 'css'));
        //        $this->addToVar('cssCode', $this->addResource('/css/all.min', 'css'));
        //        $this->addToVar('cssCode', $this->addResource('/css/v4-shims.min', 'css'));
        //        $this->addToVar('cssCode', $this->addResource('/css/login', 'css'));
    }

    public function addJS()
    {
        parent::addJS(); // TODO: Change the autogenerated stub
        //        $this->addToVar('jsCode', $this->addResource('/js/jquery.min', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/jquery-ui.min', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/jquery.tablednd.min', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/jquery.jnotify', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/Chart.min', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/select2.full.min', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/jquery.multi-select', 'css'));
        //
        //        $this->addToVar('jsCode', $this->addResource('/js/lib_head', 'css'));

        // ¿Login?
        //        $this->addToVar('jsCode', $this->addResource('/js/jstz.min', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/dst', 'css'));
        //        $this->addToVar('jsCode', $this->addResource('/js/login', 'css'));
    }

    /**
     * Assign the template.
     */
    function setTemplate(): void
    {
        $this->template = 'login';
    }
}
