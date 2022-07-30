&nbsp;
<p align="center">
    <img src="https://github.com/mosparo/mosparo/blob/master/assets/images/mosparo-logo.svg?raw=true" alt="mosparo logo contains a bird with the name Mo and the mosparo text"/>
</p>

<h1 align="center">
    PHP API Client
</h1>
<p align="center">
    This library offers the API client to communicate with mosparo to verify a submission.
</p>

-----

## Description
With this PHP library you can connect to a mosparo installation and verify the submitted data.

## Installation
Install this library by using composer:

```text
composer require mosparo/php-api-client
```

## Usage
### Client initialization
Create a new client object to use the API client.
```php
/**
 * @param string $url URL of the mosparo installation
 * @param string $publicKey Public key of the mosparo project
 * @param string $privateKey Private key of the mosparo project 
 * @param array $args Arguments for the Guzzle client
 */
$client = new Mosparo\ApiClient\Client($url, $publicKey, $privateKey, $args);
```

### Verify form data
To verify the form data, call ```validateSubmission``` with the form data in an array and the submit and validation token, which mosparo generated on the form initialization and the form data validation. The method will return true, if everything is correct and the submission is valid, or false, if there was an error and the submission should not be processed.
```php
/**
 * @param array $formData Array with the form values. All not-processed fields by mosparo (hidden, checkbox, 
 *                        radio and so on) have to be removed from this array
 * @param string $mosparoSubmitToken Submit token which mosparo returned on the form initialization
 * @param string $mosparoValidationToken Validation token which mosparo returned after the form was validated
 * @return boolean True if the submission is valid, false if something isn't valid
 */
$result = $client->validateSubmission($formData, $mosparoSubmitToken, $mosparoValidationToken);
```