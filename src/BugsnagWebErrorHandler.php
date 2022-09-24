<?php

namespace niciz\yii2bugsnag;

use niciz\yii2bugsnag\traits\BugsnagErrorHandlerTrait;
use yii\web\ErrorHandler;

/**
 * Handles exceptions in web applications
 */
class BugsnagWebErrorHandler extends ErrorHandler
{
    use BugsnagErrorHandlerTrait;
}
