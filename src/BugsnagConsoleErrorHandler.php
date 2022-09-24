<?php

namespace niciz\yii2bugsnag;

use niciz\yii2bugsnag\traits\BugsnagErrorHandlerTrait;
use yii\console\ErrorHandler;

/**
 * Handles exceptions on the console
 */
class BugsnagConsoleErrorHandler extends ErrorHandler
{
    use BugsnagErrorHandlerTrait;
}
