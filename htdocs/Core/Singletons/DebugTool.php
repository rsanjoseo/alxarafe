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

namespace Alxarafe\Core\Singletons;

use Alxarafe\Core\Base\Singleton;
use Alxarafe\Core\DebugBarCollectors\PhpCollector;
use Alxarafe\Core\DebugBarCollectors\TranslatorCollector;
use Alxarafe\Core\Providers\Translator;
use Alxarafe\Core\Utils\ClassUtils;
use DebugBar\Bridge\MonologCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;

/**
 * Class Debug
 *
 * @package Alxarafe\Helpers
 */
class DebugTool extends Singleton
{
    /**
     * Private logger instance
     *
     * @var Logger
     */
    public static Logger $logger;
    /**
     * Private render instance
     *
     * @var JavascriptRenderer
     */
    private static JavascriptRenderer $render;
    /**
     * DebugBar instance
     *
     * @var StandardDebugBar
     */
    public StandardDebugBar $debugBar;

    /**
     * DebugTool constructor.
     *
     * @throws DebugBarException
     */
    public function __construct()
    {
        self::$logger = Logger::getInstance();

        $shortName = ClassUtils::getShortName($this, $this);
        if (!defined('DEBUG')) {
            define('DEBUG', false);
            dump('La constante DEBUG no ha sido definida en DebugTool::__construct()');
            dump(debug_backtrace());
        }

        $this->debugBar = new StandardDebugBar();
        self::startTimer($shortName, $shortName . ' DebugTool Constructor');

        $this->debugBar->addCollector(new MessagesCollector('SQL'));
        $this->debugBar->addCollector(new PhpCollector());
        $this->debugBar->addCollector(new MessagesCollector('Deprecated'));
        $this->debugBar->addCollector(new MonologCollector(self::$logger->getLogger()));

        $translator = Translator::getInstance();
        $this->debugBar->addCollector(new TranslatorCollector($translator));

        $baseUrl = VENDOR_URI . '/maximebf/debugbar/src/DebugBar/Resources';
        self::$render = self::getDebugBar()->getJavascriptRenderer($baseUrl, BASE_FOLDER);

        self::stopTimer($shortName);
    }

    /**
     * Initialize the timer
     *
     * @param string $name
     * @param string $message
     */
    public function startTimer(string $name, string $message): void
    {
        $this->debugBar['time']->startMeasure($name, $message);
    }

    /**
     * Return the internal debug instance for get the html code.
     *
     * TODO: Analizar qué funciones harían falta para el html y retornar el html.
     * Tal y como está ahora mismo sería dependiente de DebugBar. DebugBar debería
     * de quedar TOTALMENTE encapsulado en esta clase.
     *
     * @return StandardDebugBar
     * @throws DebugBarException
     */
    public function getDebugBar(): StandardDebugBar
    {
        return $this->debugBar;
    }

    /**
     * Stop the timer
     *
     * @param string $name
     */
    public function stopTimer(string $name): void
    {
        $this->debugBar['time']->stopMeasure($name);
    }

    /**
     * Gets the necessary calls to include the debug bar in the page header
     *
     * @return string
     */
    public static function getRenderHeader(): string
    {
        if (defined('DEBUG') && constant('DEBUG')) {
            return self::$render->renderHead();
        }
        return '';
    }

    /**
     * Gets the necessary calls to include the debug bar in the page footer
     *
     * @return string
     */
    public static function getRenderFooter(): string
    {
        if (defined('DEBUG') && constant('DEBUG')) {
            return self::$render->render();
        }
        return '';
    }

    /**
     * Add an exception to the exceptions tab of the debug bar.
     *
     * TODO: addException is deprecated!
     *
     * @param $exception
     */
    public function addException($exception): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0];
        $caller['file'] = substr($caller['file'], strlen(BASE_FOLDER));
        $this->debugBar['exceptions']->addException($exception); // Use addThrowable instead!
        // self::$logger->info('Exception: ' . $exception->getMessage());
    }

    /**
     * Write a message in a channel (tab) of the debug bar.
     *
     * @param string $channel
     * @param string $message
     */
    public function addMessage(string $channel, string $message): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0];
        $caller['file'] = substr($caller['file'], strlen(BASE_FOLDER));
        $this->debugBar[$channel]->addMessage($caller['file'] . ' (' . $caller['line'] . '): ' . $message);
    }

}