<?php

namespace niciz\yii2bugsnag;

use Exception;
use Yii;
use yii\log\Logger;
use yii\log\Target;

class BugsnagLogTarget extends Target
{
    protected static $instance = null;

    protected static $exportedMessages = [];

    public function __construct()
    {
        parent::__construct();
        self::$instance = $this;
    }

    /**
     * @throws \Exception
     */
    public function export()
    {
        self::$exportedMessages = array_merge(self::$exportedMessages, $this->messages);

        Yii::$app->bugsnag->exportingLog = true;
        try {
            foreach ($this->messages as $message) {
                @list($message, $level, $category, $timestamp, $trace) = $message;

                if ($category == BugsnagComponent::IGNORED_LOG_CATEGORY) {
                    continue;
                }

                if ($level == Logger::LEVEL_ERROR) {
                    Yii::$app->bugsnag->notifyError($category, $message . " ($timestamp)", $trace);
                } elseif ($level == Logger::LEVEL_WARNING) {
                    Yii::$app->bugsnag->notifyWarning($category, $message . " ($timestamp)", $trace);
                }
            }

            Yii::$app->bugsnag->exportingLog = false;
        } catch (Exception $e) {
            Yii::$app->bugsnag->exportingLog = false;
            throw $e;
        }
    }

    /**
     * @return array
     */
    public static function getMessages()
    {
        $messages = self::$exportedMessages;

        if (isset(self::$instance)) {
            $messages = array_merge($messages, self::$instance->messages);
        }

        static $levelMap = [
            Logger::LEVEL_ERROR => 'error',
            Logger::LEVEL_WARNING => 'warning',
            Logger::LEVEL_INFO => 'info',
            Logger::LEVEL_TRACE => 'trace',
            Logger::LEVEL_PROFILE => 'profile',
        ];

        return array_map(
            function ($message) use ($levelMap) {
                @list($message, $level, $category, $timestamp) = $message;

                if (!is_string($message)) {
                    $message = print_r($message, true);
                }

                $date = date('Y-m-d H:i:s', (int)$timestamp) . '.' . substr(fmod($timestamp, 1), 2, 4);
                return "{$levelMap[$level]} - ({$category}) @ {$date} - {$message}";
            },
            self::$exportedMessages,
            isset(self::$instance) ? self::$instance->messages : []
        );
    }
}
