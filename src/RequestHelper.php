<?php

/**
 * A helper class to support the API client
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 * @copyright 2021-2022 mosparo
 * @license MIT
 */

namespace Mosparo\ApiClient;

/**
 * A helper class to support the API client
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 */
class RequestHelper
{
    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * Constructs the object
     *
     * @param string $publicKey Public key of the mosparo project
     * @param string $privateKey Private key of the mosparo project
     */
    public function __construct(string $publicKey, string $privateKey)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * Creates the HMAC hash for the given string
     *
     * @param string $data
     * @return string
     */
    public function createHmacHash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->privateKey);
    }

    /**
     * Generates the hash for all form values and prepares the form data
     * to submit it to the verification API.
     *
     * @param array $formData
     * @return array
     */
    public function prepareFormData(array $formData): array
    {
        $formData = $this->cleanupFormData($formData);

        $data = [];
        foreach ($formData as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->prepareFormData($value);
            } else {
                $data[$key] = hash('sha256', $value);
            }
        }

        ksort($data);

        return $data;
    }

    /**
     * Cleanups the given form data. The method removes possible
     * mosparo fields from the data, converts all line breaks and converts
     * the field keys to lower case characters.
     *
     * @param array $formData
     * @return array
     */
    public function cleanupFormData(array $formData): array
    {
        if (isset($formData['_mosparo_submitToken'])) {
            unset($formData['_mosparo_submitToken']);
        }

        if (isset($formData['_mosparo_validationToken'])) {
            unset($formData['_mosparo_validationToken']);
        }

        foreach ($formData as $key => $value) {
            $formData[$key] = str_replace("\r\n", "\n", $value);
        }

        $formData = array_change_key_case($formData, CASE_LOWER);
        ksort($formData);

        return $formData;
    }

    /**
     * Creates the HMAC hash for the given form data
     *
     * @param array $formData
     * @return string
     */
    public function createFormDataHmacHash(array $formData): string
    {
        return $this->createHmacHash($this->toJson($formData));
    }

    /**
     * Converts the given array into a json string.
     *
     * @param array $inData
     * @return string
     */
    public function toJson(array $inData): string
    {
        $data = [];

        foreach ($inData as $key => $value) {
            if ($value === false) {
                $value = '0';
            }

            if (is_array($value)) {
                $data[$key] = $this->toJson($value);
            } else if (!is_string($value)) {
                $data[$key] = (string) $value;
            } else {
                $data[$key] = $value;
            }
        }

        return json_encode($data);
    }
}