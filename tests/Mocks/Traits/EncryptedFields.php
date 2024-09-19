<?php

namespace Finxp\Flexcube\Tests\Mocks\Traits;

use Finxp\Utilities\Libraries\Encrypter\Facades\Encrypter;

trait EncryptedFields
{
    protected static function bootEncryptedFields()
    {
        static::saving(function ($model) {
            $encryptedFields = $model->getEncryptedFields();

            if (!empty($encryptedFields) && $model->isEncryptedFieldsDirty()) {
                foreach ($encryptedFields as $encryptedField) {
                    $payload = $model->$encryptedField;

                    if (!is_string($payload) && !is_null($payload)) {
                        $payload = json_encode($payload);
                    }

                    if (!is_null($payload)) {
                        $encodedCipherText = base64_encode(
                            Encrypter::encryptString($payload)
                        );

                        $model->$encryptedField = $encodedCipherText;
                    }
                }
            }
        });
    }

    public function getEncryptedFields(): array
    {
        return self::$ENCRYPTED_FIELDS;
    }

    protected function isEncryptedFieldsDirty(): bool
    {
        foreach (self::getEncryptedFields() as $field) {
            if ($this->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (
            parent::isClean($key) &&
            is_string($value) &&
            isset(self::$ENCRYPTED_FIELDS) &&
            in_array($key, self::$ENCRYPTED_FIELDS)
        ) {
            $value = Encrypter::decryptString(base64_decode($value));

            $parsedValue = json_decode($value);

            if (!is_null($parsedValue)) {
                $value = $parsedValue;
            }
        }

        return $value;
    }
}
