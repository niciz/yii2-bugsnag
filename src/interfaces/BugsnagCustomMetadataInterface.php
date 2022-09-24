<?php

namespace niciz\yii2bugsnag\interfaces;

/**
 * Allows an exception to set custom metadata to pass to Bugsnag
 */
interface BugsnagCustomMetadataInterface
{
    /**
     * Gets metadata for this exception
     * @return array
     */
    public function getMetadata();
}
