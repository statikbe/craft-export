<?php
/**
 * Export plugin for Craft CMS 3.x
 *
 * Export elements from Craft
 *
 * @link      https://statik.be
 * @copyright Copyright (c) 2019 Statik
 */

namespace statikbe\export\services;

use Craft;
use craft\base\Component;
use statikbe\export\Export;


class Mail extends Component
{
    public function sendMail($email, $file)
    {
        $mailer = Craft::$app->getMailer();
        $message = $mailer->compose();
        $message->setFrom($mailer->from);
        $message->setTo($email);
        $message->setSubject(Craft::t('export', 'Uw export in bijlage'));
        Craft::$app->view->setTemplateMode('cp');
        $message->setHtmlBody(Craft::$app->view->renderTemplate('export/_includes/mail/_export'));
        $message->attach($file);
        $result = $message->send($mailer);
    }
}