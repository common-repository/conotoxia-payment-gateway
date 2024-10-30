<?php

declare(strict_types=1);

namespace CKPL\Pay\Exception\Api;

use CKPL\Pay\Exception\Api\ValidationCollection\ValidationCollection;
use CKPL\Pay\Exception\Api\ValidationCollection\ValidationCollectionInterface;
use CKPL\Pay\Exception\Http\HttpBadRequestException;

/**
 * Class ValidationErrorException.
 *
 * @package CKPL\Pay\Exception\Api
 */
class ValidationErrorException extends HttpBadRequestException implements ApiExceptionInterface
{
    /**
     * @type string
     */
    const TYPE = 'validation-error';

    /**
     * @var ValidationCollectionInterface
     */
    protected $validationCollection;

    /**
     * @param bool $recreate
     *
     * @return ValidationCollectionInterface
     */
    public function createValidationCollection(bool $recreate = false): ValidationCollectionInterface
    {
        if ($recreate || null === $this->validationCollection) {
            $this->validationCollection = new ValidationCollection();
        }

        return $this->validationCollection;
    }

    /**
     * @return array
     */
    public function getLogMessages(): array
    {
        return $this->getErrorMessagesAsJson();
    }

    /**
     * @param string $languageCode
     * @return array
     */
    public function getLocalizedMessages(string $languageCode = 'en'): array
    {
        return $this->getErrorMessages($languageCode);
    }

    /**
     * @param string $languageCode
     *
     * @return array
     */
    private function getErrorMessages(string $languageCode = 'en'): array
    {
        $messages = [];
        $languageCode = $this->getSupportedLanguage($languageCode);

        foreach ($this->validationCollection->getErrors() as $error) {
            $fieldName = '';

            if (isset($error['context-key'])) {
                $fieldName = '\'' . $error['context-key'] .'\'';
            }

            $messages[] = $this->getErrorMessage($error, $fieldName, $languageCode);
        }

        return $messages;
    }

    /**
     * @param string $languageCode
     *
     * @return string
     */
    private function getSupportedLanguage(string $languageCode): string
    {
        $supportedLanguages = ['en', 'pl'];

        if (!in_array($languageCode, $supportedLanguages)) {
            $languageCode = 'en';
        }

        return $languageCode;
    }

    /**
     * @param array $error
     * @param string $fieldName
     * @param string $languageCode
     *
     * @return string
     */
    private function getErrorMessage(array $error, string $fieldName, string $languageCode): string
    {
        $errorKey = $error['message-key'];
        $message = '';

        switch ($errorKey) {
            case 'not-null':
            case 'not-empty':
                $message = [
                    'en' => 'The ' . $fieldName . ' field cannot be empty.',
                    'pl' => 'Pole ' . $fieldName . ' nie może być puste.'
                ];
                break;
            case 'positive':
                $message = [
                    'en' => 'Value must be greater than zero.',
                    'pl' => 'Wartość musi być większa niż 0.'
                ];
                break;
            case 'out-of-range':
                $min = $error['params']['min'];
                $max = $error['params']['max'];

                $min = $min === 0 ? 1 : $min;

                $message = [
                    'en' => 'The ' . $fieldName . ' field should be between ' . $min . ' and ' . $max . ' characters long.',
                    'pl' => 'Pole ' . $fieldName . ' powinno mieć długość od ' . $min . ' do ' . $max . ' znaków.'
                ];
                break;
            case 'positive-or-zero':
                $min = $error['params']['min'];
                $max = $error['params']['max'];

                $message = [
                    'en' => 'Value must be in range of ' . $min . ' to ' . $max . '.',
                    'pl' => 'Wartość musi być w zakresie od ' . $min . ' do ' . $max . '.'
                ];
                break;
            case 'incorrect-format':
                $message = [
                    'en' => 'Incorrect format of the ' . $fieldName . ' field.',
                    'pl' => 'Niepoprawny format pola ' . $fieldName . '.'
                ];
                break;
            case 'value-too-high':
                $max = $error['params']['max'];

                $message = [
                    'en' => 'The ' . $fieldName . ' field cannot be longer than ' . $max .' characters.',
                    'pl' => 'Pole ' . $fieldName . ' nie może być dłuższe niż ' . $max . ' znaków.'
                ];
                break;
            default:
                $message = [
                    'en' => 'Error occurred.',
                    'pl' => 'Wystąpił błąd.'
                ];
                break;
        }

        return $message[$languageCode];
    }

    /**
     * @return array
     */
    private function getErrorMessagesAsJson(): array
    {
        $messages = [];

        foreach ($this->validationCollection->getErrors() as $error) {
            $messages[] = json_encode($error);
        }

        return $messages;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE;
    }
}