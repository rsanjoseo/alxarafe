<?php

namespace Alxarafe\Tools\DebugBarCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Class MonologCollector
 *
 * This class collects all PHP errors, notice, advices, trigger_error, ...
 * Supports 15 different types included.
 *
 * @package Alxarafe\Lib\DebugBarCollector
 */
class PhpCollector extends DataCollector implements Renderable
{
    /**
     * Collector name.
     *
     * @var string
     */
    private static $name;

    /**
     * List of messages. Each item includes:
     *  'message', 'message_html', 'is_string', 'label', 'time'.
     *
     * @var array
     */
    private static $messages = [];

    /**
     * PHPCollector constructor.
     *
     * @param string $name The name used by this collector widget.
     */
    public function __construct(string $name = 'Error handler')
    {
        self::$name = $name;
        set_error_handler([$this, 'errorHandler'], E_ALL);
    }

    /**
     * Called by the DebugBar when data needs to be collected.
     *
     * @return array Collected data.
     */
    public function collect(): array
    {
        $messages = $this->getMessages();
        return [
            'count' => count($messages),
            'messages' => $messages,
        ];
    }

    /**
     * Returns a list of messages ordered by their timestamp.
     *
     * @return array A list of messages ordered by time.
     */
    public function getMessages(): array
    {
        $messages = self::$messages;

        usort($messages, function ($itemA, $itemB) {
            if ($itemA['time'] === $itemB['time']) {
                return 0;
            }
            return $itemA['time'] < $itemB['time'] ? -1 : 1;
        });

        return $messages;
    }

    /**
     * Returns the unique name of the collector.
     *
     * @return string The widget name.
     */
    public function getName(): string
    {
        return self::$name;
    }

    /**
     * Returns a hash where keys are control names and their values an array of options as defined in
     * {@see DebugBar\JavascriptRenderer::addControl()}
     *
     * @return array Needed details to render the widget.
     */
    public function getWidgets(): array
    {
        $name = self::$name;
        return [
            $name => [
                'icon' => 'list',
                'widget' => 'PhpDebugBar.Widgets.MessagesWidget',
                'map' => "$name.messages",
                'default' => '[]',
            ],
            "$name:badge" => [
                'map' => "$name.count",
                'default' => 'null',
            ],
        ];
    }

    /**
     * Exception error handler. Called from constructor with set_error_handler to add all details.
     *
     * @param int $severity Error type.
     * @param string $message Message of error.
     * @param string $fileName File where error is generated.
     * @param int $line Line number where error is generated.
     */
    public function errorHandler($severity, $message, $fileName, $line)
    {
        for ($i = 0; $i < 15; $i++) {
            if ($type = $severity & (2 ** $i)) {
                $label = self::friendlyErrorType($type);
                self::$messages[] = [
                    'message' => $message . ' (' . $fileName . ':' . $line . ')',
                    'message_html' => null,
                    'is_string' => true,
                    'label' => $label,
                    'time' => microtime(true),
                ];
            }
        }
    }

    /**
     * Return error name from error code.
     *
     * @info http://php.net/manual/es/errorfunc.constants.php
     *
     * @param int $type Error code.
     *
     * @return string Error name.
     */
    private static function friendlyErrorType(int $type): string
    {
        $errors = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED',
        ];

        return $errors[$type] ?? '';
    }
}
