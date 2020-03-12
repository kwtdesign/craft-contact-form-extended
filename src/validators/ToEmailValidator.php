<?php

namespace craftcontactformextended\validators;

use Craft;
use yii\validators\Validator;

class ToEmailValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $emails = $model->$attribute;

        foreach ( $emails as $key => $email )
        {
            if ( !filter_var($email, FILTER_VALIDATE_EMAIL) )
            {
                $model->addError($attribute . $key, 'Invalid E-Mail.');
            }
        }
    }
}
