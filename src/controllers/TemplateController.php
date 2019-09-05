<?php
/**
 * Export plugin for Craft CMS 3.x
 *
 * Export elements from Craft
 *
 * @link      https://statik.be
 * @copyright Copyright (c) 2019 Statik
 */

namespace statikbe\export\controllers;

use statikbe\export\Export;

use Craft;
use craft\web\Controller;
use statikbe\export\models\ExportModel;
use statikbe\export\services\Elements;
use statikbe\export\services\Exports;

/**
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Statik
 * @package   Export
 * @since     1.0.0
 */
class TemplateController extends Controller
{

    protected $allowAnonymous = [];

    // export/welcome
    public function actionWelcome()
    {
        return $this->renderTemplate('export/_welcome');
    }

    // export/exports
    public function actionOverview()
    {
        $variables['exports'] = Exports::instance()->getExports();

        return $this->renderTemplate('export/_overview', $variables);
    }

    // export/exports/new
    // export/exports/{id}
    public function actionEditExport($exportId = null)
    {
        $variables = [];

        if ($exportId) {
            $variables['export'] = Exports::instance()->getExportById($exportId);
        } else {
            $variables['export'] = new ExportModel();
        }

        $variables['elements'] = Elements::instance()->getElementTypes();
        $variables['exportTypes'] = [
            'csv' => 'CSV',
            'json' => 'JSON',
        ];

        return $this->renderTemplate('export/_edit', $variables);
    }

    // export/exports/{id}/settings
    public function actionEditSettings($exportId = null)
    {
        $variables = [];

        if ($exportId) {
            $export = Exports::instance()->getExportById($exportId);
            $variables['export'] = $export;
            $fields = Elements::instance()->getFieldsForElement($export->elementType, $export->elementGroup);
            $variables['fields'] = $fields;

            return $this->renderTemplate('export/_settings', $variables);
        } else {
            return $this->redirect('/admin/export/exports/new');
        }
    }

    // export/exports/{id}/run
    public function actionExportReady($exportId = null)
    {
        $variables = [];

        if ($exportId) {
            $variables['export'] = Exports::instance()->getExportById($exportId);
            return $this->renderTemplate('export/_run', $variables);
        } else {
            return $this->redirect('/admin/export/exports/new');
        }
    }

    // export/exports/{id}/download
    public function actionExportDownload($exportId = null)
    {
        $variables = [];

        if ($exportId) {
            $variables['export'] = Exports::instance()->getExportById($exportId);
            return $this->renderTemplate('export/_download', $variables);
        } else {
            return $this->redirect('/admin/export/exports/new');
        }
    }
}
