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
use craft\helpers\Json;
use craft\web\Controller;
use statikbe\export\models\ExportModel;
use statikbe\export\services\Download;
use statikbe\export\services\Elements;
use statikbe\export\services\Exports;
use Cocur\Slugify\Slugify;
use statikbe\export\services\Mail;

/**
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Statik
 * @package   Export
 * @since     1.0.0
 */
class ExportController extends Controller
{

    protected $allowAnonymous = [];

    // save export (name and main settings)
    public function actionSaveExport()
    {
        $this->requirePostRequest();

        $export = $this->_getModelFromPost();

        if (!$export->validate()) {
            $variables['export'] = $export;
            $variables['errors'] = $export->getErrors();
            $variables['elements'] = Elements::instance()->getElementTypes();
            $variables['exportTypes'] = [
                'csv' => 'CSV',
                'json' => 'JSON',
            ];

            return $this->renderTemplate('export/_edit', $variables);
        } else {
            return $this->_saveAndRedirect($export, 'export/exports/', true, 'settings');
        }
    }

    // save export fields settings
    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        $export = Exports::instance()->getExportById(Craft::$app->request->getBodyParam('exportId'));

        $fields = Craft::$app->request->getBodyParam('fields');
        $json = Json::encode($fields);
        $export->settings = $json;

        return $this->_saveAndRedirect($export, 'export/exports/', true, 'run');

    }

    // reorder exports on overview
    public function actionReorderExports()
    {
        $this->requirePostRequest();

        $exportIds = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));
        $exportIds = array_filter($exportIds);
        Exports::instance()->reorderExports($exportIds);

        return $this->asJson(['success' => true]);
    }

    // delete export on overview
    public function actionDeleteExport()
    {
        $this->requirePostRequest();

        $exportId = Craft::$app->request->getRequiredBodyParam('id');

        Exports::instance()->deleteExportById($exportId);

        return $this->asJson(['success' => true]);
    }

    // download export
    public function actionDownloadExport($exportId = null)
    {
        if (empty($exportId)) {
            Craft::$app->session->setError(Craft::t('export', 'Unable to export file with id 0.'));
            return null;
        }

        $export = Exports::instance()->getExportById($exportId);
        if ($export) {
            // Create the export directory in the storage folder
            $path = Craft::$app->path->getTempPath() . Export::EXPORT_PATH;
            if (!is_dir($path)) {
                mkdir($path);
            }

            // Create the file
            $file = Download::instance()->downloadExport($export, $path);

            // Open the file
            header('Content-type: application/csv');
            header('Content-Disposition: inline; filename="' . $file . '"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            echo file_get_contents($path . $file);
            die();
        }
    }

    // Run export task
    /*public function actionRunExport($exportId) {

        if (!isset($exportId)) {
            $exportId = Craft::$app->request->getBodyParam('exportId');
        }

        $export = Exports::instance()->getExportById($exportId);

        $return = Craft::$app->request->getBodyParam('return') ?: 'export';

        $variables['export'] = $export;
        $variables['job'] = Exports::instance()->runExport($export);

        return $this->redirect($return);
    }*/

    // send mail with export
    public function actionSendExport() {
        if (Craft::$app->request->getBodyParam('emailaddress')) {
            if (!Craft::$app->request->getBodyParam('exportId')) {
                Craft::$app->session->setError(Craft::t('export', 'Unable to export file with id 0.'));
                return null;
            }

            $exportId = Craft::$app->request->getBodyParam('exportId');
            $export = Exports::instance()->getExportById($exportId);
            if ($export) {
                // Create the export directory in the storage folder
                $path = Craft::$app->path->getTempPath() . Export::EXPORT_PATH;
                if (!is_dir($path)) {
                    mkdir($path);
                }

                // Create the file
                $fileName = Download::instance()->downloadExport($export, $path);

                // Send the e-mail
                $mail = Craft::$app->request->getBodyParam('emailaddress');
                $file = $path . $fileName;
                Mail::instance()->sendMail($mail, $file);
            }
            Craft::$app->session->setNotice(Craft::t('export', 'Export send.'));
        } else {
            Craft::$app->session->setError(Craft::t('export', 'Unable to send mail: no e-mail address filled in.'));
            return null;
        }

    }

    // save the export with fields
    private function _saveAndRedirect($export, $redirect, $withId = false, $redirectEdge)
    {
        if (!Exports::instance()->saveExport($export)) {
            Craft::$app->session->setError(Craft::t('export', 'Unable to save export.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'export' => $export,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('export', 'Export saved.'));

        if ($withId) {
            $redirect = $redirect . $export->id . '/' . $redirectEdge;
        }

        return $this->redirect($redirect);
    }

    // fill model with the post fields
    private function _getModelFromPost()
    {
        $this->requirePostRequest();

        if (Craft::$app->request->getBodyParam('exportId')) {
            $export = Exports::instance()->getExportById(Craft::$app->request->getBodyParam('exportId'));
        } else {
            $export = new ExportModel();
        }

        $export->name = Craft::$app->request->getBodyParam('name', $export->name);


        //slugify the filename so there won't be special characters and spaces
        $fileName = Craft::$app->request->getBodyParam('filename', '');
        $slugify = new Slugify();
        $fileNameAsSlug = $slugify->slugify($fileName);
        $export->filename = $fileNameAsSlug;

        // file the model with the body fields
        $export->exportType = Craft::$app->request->getBodyParam('exportType', $export->exportType);
        $export->elementType = Craft::$app->request->getBodyParam('elementType', $export->elementType);
        $export->elementGroup = Craft::$app->request->getBodyParam('elementGroup', $export->elementGroup);
        $export->siteId = Craft::$app->request->getBodyParam('siteId', $export->siteId);

        return $export;
    }

}
