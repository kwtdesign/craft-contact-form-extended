<?php

namespace craftcontactformextended\models;

use craftcontactformextended\validators\ToEmailValidator;
use craft\base\Model;
use yii\base\ModelEvent;

class Settings extends Model
{
    private $form = 'default';

    public $fields = [
        'fromEmail' => [
            'name'    => 'E-Mail',
            'filters' => 'trim|sanitize_email',
            'rules'   => 'required|valid_email'
        ]
    ];

    public $toEmail = [];

    public function rules()
    {
        return [
            [['toEmail'], ToEmailValidator::class]
        ];
    }

    public function setForm(ModelEvent $event)
    {
        $submission = $event->sender;

        if ( is_array($submission->message) )
        {
            foreach ( $submission->message as $field => $value )
            {
                if ( $field == 'form' )
                {
                    $this->form = $value;

                    break;
                }
            }
        }
    }

    public function getFields(): Array
    {
        $fields = [];

        foreach ( ($this->fields[$this->form] ?? []) as $key => $field )
        {
            if ( isset($field['name']) )
            {
                $fields[$key] = $field['name'];
            }
        }

        return $fields;
    }

    public function getFieldFilters(): Array
    {
        $filters = [];

        foreach ( ($this->fields[$this->form] ?? []) as $key => $field )
        {
            if ( isset($field['filters']) )
            {
                $filters[$key] = $field['filters'];
            }
        }

        return $filters;
    }

    public function getFieldRules(): Array
    {
        $rules = [];

        foreach ( ($this->fields[$this->form] ?? []) as $key => $field )
        {
            if ( isset($field['rules']) )
            {
                $rules[$key] = $field['rules'];
            }
        }

        return $rules;
    }
}
