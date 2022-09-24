<?php

namespace niciz\yii2bugsnag;

use Bugsnag\Callbacks\CustomUser;
use Bugsnag\Client;
use Bugsnag\Handler;
use Bugsnag\Report;
use niciz\yii2bugsnag\interfaces\BugsnagCustomContextInterface;
use niciz\yii2bugsnag\interfaces\BugsnagCustomMetadataInterface;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

class BugsnagComponent extends Component
{
    const IGNORED_LOG_CATEGORY = 'Bugsnag notified exception';

    public $bugsnag_api_key;

    public $releaseStage = null;

    public $notifyReleaseStages;

    public $filters = ['password', 'cookie', 'authorization', 'php-auth-user', 'php-auth-pw', 'php-auth-digest'];

    protected $client;

    /**
     * True if we are in BugsnagLogTarget::export(), then don't trigger a flush, causing an
     * infinite loop
     * @var boolean
     */
    public $exportingLog = false;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if (empty($this->bugsnag_api_key)) {
            throw new InvalidConfigException("bugsnag_api_key must be set");
        }

        $this->client = Client::make($this->bugsnag_api_key);

        // Reporting unhandled exceptions
        Handler::register($this->getClient());

        if (!empty($this->notifyReleaseStages)) {
            $this->client->setNotifyReleaseStages($this->notifyReleaseStages);
        }

        $this->client->setRedactedKeys($this->filters);

        $this->client->setBatchSending(true);

        if (empty($this->releaseStage)) {
            $this->releaseStage = defined('YII_ENV') ? YII_ENV : 'production';
        }

        $this->client->setNotifier([
            'name' => 'Yii2 Bugsnag',
            'version' => '1.0.0',
            'url' => 'https://github.com/niciz/yii2-bugsnag',
        ]);

        $this->client->registerDefaultCallbacks();

        $this->client->registerCallback(function (Report $report) {
            if (!$this->exportingLog) {
                Yii::getLogger()->flush(true);
            }

            $report->setMetadata([
                'logs' => BugsnagLogTarget::getMessages(),
            ]);
        });

        Yii::debug("Setting release stage to $this->releaseStage.", __CLASS__);
        $this->client->setReleaseStage($this->releaseStage);
    }

    /**
     * Returns user information
     *
     * @return array|null
     */
    public function getUserData()
    {
        // Don't crash if not using yii\web\User
        if (!Yii::$app->has('user') || !isset(Yii::$app->user->id)) {
            return null;
        }

        return [
            'id' => Yii::$app->user->id,
        ];
    }

    public function getClient()
    {
        $clientUserData = $this->getUserData();
        if (!empty($clientUserData)) {
            $this->client->registerCallback(new CustomUser(function () use ($clientUserData) {
                return $clientUserData;
            }));
        }

        return $this->client;
    }

    public function notifyError($category, $message, $trace = null)
    {
        $this->getClient()->notifyError($category, $message, function ($report) use ($trace) {
            $report->setSeverity('error');
            $report->setMetaData(['trace' => $trace]);
        });
    }

    public function notifyWarning($category, $message, $trace = null)
    {
        $this->getClient()->notifyError($category, $message, function ($report) use ($trace) {
            $report->setSeverity('warning');
            $report->setMetaData(['trace' => $trace]);
        });
    }

    public function notifyInfo($category, $message, $trace = null)
    {
        $this->getClient()->notifyError($category, $message, function ($report) use ($trace) {
            $report->setSeverity('info');
            $report->setMetaData(['trace' => $trace]);
        });
    }

    public function notifyException($exception, $severity = null)
    {
        $metadata = [];
        if ($exception instanceof BugsnagCustomMetadataInterface) {
            $metadata = $exception->getMetadata();
        }

        if ($exception instanceof BugsnagCustomContextInterface) {
            $this->getClient()->setContext($exception->getContext());
        }

        $this->getClient()->notifyException($exception, function ($report) use ($severity, $metadata) {
            $report->setSeverity($severity);
            $report->setMetaData($metadata);
        });
    }

    public function runShutdownHandler()
    {
        if (!$this->exportingLog) {
            Yii::getLogger()->flush(true);
        }

        Handler::register($this->getClient())->shutdownHandler();
    }
}
