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
1. Create a project in your mosparo installation
2. Include the mosparo script in your form
```html
<div id="mosparo-box"></div>

<script src="https://[URL]/build/mosparo-frontend.js" defer></script>
<script>
    var m;
    window.onload = function(){
        m = new mosparo('mosparo-box', 'https://[URL]', '[UUID]', '[PUBLIC_KEY]', {loadCssResource: true});
    };
</script>
```
3. Include the library in your project
```text
composer require mosparo/php-api-client
```
4. After the form was submitted, verify the data before processing it
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

$client = new Mosparo\ApiClient\Client($url, $publicKey, $privateKey, [ /* Options for Guzzle */ ]);

$mosparoSubmitToken = $_POST['_mosparo_submitToken'];
$mosparoValidationToken = $_POST['_mosparo_validationToken'];

$result = $client->verifySubmission($_POST, $mosparoSubmitToken, $mosparoValidationToken);

if ($result->isSubmittable()) {
    // Send the email or process the data
} else {
    // Show error message
}
```

## API Documentation

### Client

#### Client initialization
Create a new client object to use the API client.
```php
/**
 * @param string $url URL of the mosparo installation
 * @param string $publicKey Public key of the mosparo project
 * @param string $privateKey Private key of the mosparo project 
 * @param array $args Arguments for the Guzzle client, see https://docs.guzzlephp.org/en/stable/request-options.html
 */
$client = new Mosparo\ApiClient\Client($url, $publicKey, $privateKey, $args);
```

#### Verify form data
To verify the form data, call `verifySubmission` with the form data in an array and the submit and validation tokens, which mosparo generated on the form initialization and the form data validation. The method will return a `VerificationResult` object.
```php
/**
 * @param array $formData Array with the form values. All not-processed fields by mosparo (hidden, checkbox, 
 *                        radio and so on) have to be removed from this array
 * @param string $mosparoSubmitToken Submit token which mosparo returned on the form initialization
 * @param string $mosparoValidationToken Validation token which mosparo returned after the form was validated
 * @return \Mosparo\ApiClient\VerificationResult Returns a VerificationResult object with the response from mosparo
 * 
 * @throws \Mosparo\ApiClient\Exception Submit or validation token not available.
 * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
 */
$result = $client->verifySubmission($formData, $mosparoSubmitToken, $mosparoValidationToken);
```

#### Request the statistical data
mosparo also has an API method to get the statistical data for a project. You can use the method `getStatisticByDate` to get the statistical data. You can specify the range in seconds or a start date from which mosparo should return the statistical data. This method will return a `StatisticResult` object.
```php
/**
 * @param int $range = 0 The range in seconds for which mosparo should return the statistical data (will be rounded up to a full day since mosparo v1.1)
 * @param \DateTime $startDate = null The Start date from which on mosparo should return the statistical data (requires mosparo v1.1)
 * @return \Mosparo\ApiClient\StatisticResult Returns a StatisticResult object with the response from mosparo
 * 
 * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
 */
$result = $client->getStatisticByDate($range, $startDate);
```

### VerificationResult

#### Constants
- FIELD_NOT_VERIFIED: 'not-verified'
- FIELD_VALID: 'valid'
- FIELD_INVALID: 'invalid'

#### isSubmittable(): boolean
Returns true, if the form is submittable. This means that the verification was successful and the 
form data are valid.

#### isValid(): boolean
Returns true, if mosparo determined the form as valid. The difference to `isSubmittable()` is, that this
is the original result from mosparo while `isSubmittable()` also checks if the verification was done correctly.

#### getVerifiedFields(): array (see Constants)
Returns an array with all verified field keys.

#### getVerifiedField($key): string (see Constants)
Returns the verification status of one field.

#### hasIssues(): boolean
Returns true, if there were verification issues.

#### getIssues(): array
Returns an array with all verification issues.

### StatisticResult

#### getNumberOfValidSubmissions(): int
Returns the total number of valid submissions in the requested date range.

#### getNumberOfSpamSubmissions(): int
Returns the total number of spam submissions in the requested date range.

#### getNumbersByDate(): array
Returns an array with all statistical data for the requested time range. The date is the key in the array, while an array is set as a value. The array contains a key `numberOfValidSubmissions` with the number of valid submissions and a key `numberOfSpamSubmissions` with the number of spam submissions.
