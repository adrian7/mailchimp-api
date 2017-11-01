# Devlib/MailChimp-API

Simple MailChimp API Wrapper

### Install
`composer require devlib/mailchimp-api`

### Usage
```php
use DevLib\API\MailChimp\MailChimp;

$apiKey  = getenv('MC_API_KEY');
$list_id = getenv('MC_LIST_ID');

$api     = new MailChimp($apiKey, $list_id);
```

### Subscribe user
```php
use DevLib\API\MailChimp\MailChimp;
use MailChimpAPIException

$email = 'you@example.com';

try{

    $api->subscribe($email, [
        'FNAME' => 'Emma',
        'LNAME' => 'Doe'
    ]);

}catch(MailChimp\MailChimpAPIException $e){
    echo ( 'Error: ' . $e->getMessage() );
}
```
