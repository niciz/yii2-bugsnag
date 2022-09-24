<?php

namespace niciz\yii2bugsnag\interfaces;

/**
 * Allows an exception to set a custom context (instead of defaulting to URL)
 */
interface BugsnagCustomContextInterface
{
    /**
     * Gets the context for this exception
     * @return string
     */
    public function getContext();
}
