<?php

namespace craft\feedme\queue\jobs;

use Craft;
use craft\feedme\Plugin;
use craft\queue\BaseJob;

class ExportJob extends BaseJob
{
    // Properties
    // =========================================================================

    public $export;
    public $processedElementIds;


    public function execute($queue)
    {
        // TODO ?
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('export', 'Running {name} export.', ['name' => $this->export->name]);
    }
}
