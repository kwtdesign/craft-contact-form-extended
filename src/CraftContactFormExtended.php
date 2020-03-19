<?php

namespace craftcontactformextended;

use Craft;
use craftcontactformextended\validators\GumpValidator;
use craft\base\Plugin;
use craft\contactform\Mailer;
use craft\contactform\events\SendEvent;
use craft\contactform\models\Submission;
use craft\helpers\StringHelper;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\web\NotFoundHttpException;

class CraftContactFormExtended extends Plugin
{
    public static $plugin;

    public $schemaVersion = '1.0.0';

    public $hasCpSettings = true;

    protected $validator = null;

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        Event::on(Submission::class, Submission::EVENT_BEFORE_VALIDATE, function(ModelEvent $event)
        {
            $event = $this->getValidator()->check($event);
        });

        Event::on(Mailer::class, Mailer::EVENT_BEFORE_SEND, function(SendEvent $event)
        {
            $siteHandle = Craft::$app->getSites()->getCurrentSite()->handle;

            $toEmails = CraftContactFormExtended::$plugin->getSettings()->toEmail[$siteHandle] ?? [];

            if ( $toEmails )
            {
                $event->toEmails = is_string($toEmails) ? StringHelper::split($toEmails) : $toEmails;
            }

            $form = 'default';

            $variables = [];

            $variables['fromName']  = $event->submission->fromName;
            $variables['fromEmail'] = $event->submission->fromEmail;
            $variables['subject']   = $event->submission->subject;

            if ( is_array($event->submission->message) )
            {
                foreach ( $event->submission->message as $field => $value )
                {
                    $variables[$field] = $value;

                    if ( $field == 'siteHandle' )
                    {
                        $toEmails = ContactFormExtended::$plugin->getSettings()->toEmail[$value] ?? [];

                        if ( $toEmails )
                        {
                            $event->toEmails = is_string($toEmails) ? StringHelper::split($toEmails) : $toEmails;
                        }
                    }

                    if ( $field == 'form' )
                    {
                        $form = $value;
                    }
                }
            }
            else
            {
                $variables['message'] = $event->submission->message;
            }

            $view = Craft::$app->getView();
            $front_templates_path = $view->getTemplatesPath();
            $plugin_templates_path = self::getInstance()->getBasePath() . '/templates';

            if ( file_exists($front_templates_path . '/craft-contact-form-extended/email-' . $form . '.html') )
            {
                $html = $view->renderTemplate('craft-contact-form-extended/email-' . $form, $variables);
            }
            else if ( file_exists($plugin_templates_path . 'email-' . $form . '.html') )
            {
                $view->setTemplatesPath($plugin_templates_path);

                $html = $view->renderTemplate('email-' . $form, $variables);
            }
            else
            {
                $view->setTemplatesPath($plugin_templates_path);

                $html = $view->renderTemplate('email-default', $variables);
            }

            $event->message->setHtmlBody($html);

            if ( $view->getTemplatesPath() != $front_templates_path )
            {
                $view->setTemplatesPath($front_templates_path);
            }
        });
    }

    protected function getValidator()
    {
        if ( $this->validator !== null )
        {
            return $this->validator;
        }
        
        return (new GumpValidator((new \GUMP), $this->getSettings()));
    }

    protected function createSettingsModel()
    {
        return new \craftcontactformextended\models\Settings();
    }

    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('craft-contact-form-extended/settings', [
            'settings' => $this->getSettings()
        ]);
    }
}
