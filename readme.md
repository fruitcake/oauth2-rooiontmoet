# RooiOntmoet Provider for OAuth 2.0 Client

This package provides RooiOntmoet OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Requirements

* PHP 5.5 or higher

## Installation

Require this package with Composer:

```
composer require fruitcake/oauth2-rooiontmoet:"~1.0@dev"
```

> During development, the `@dev` flag is required.

## Usage

### Available Scopes

* profile: Default public profile (full name + ID)
* email: The user email

You will need to apply for API access. By default, only `profile` access is granted.
Admin users can have access to more scopes, depending on the access level. Contact Fruitcake for more information.

> Only verified endpoints have access, so make sure you register those first!

### Authorization Code Flow

```php
session_start();

// Create Provider
$provider = new RooiOntmoet\OAuth2\Client\Provider\RooiOntmoet([
  'clientId'          => 'my-client-id',
  'clientSecret'      => 'my-client-secret',
  'redirectUri'       => 'http://my-domain.com/login-callback.php',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl([
      'scope' => ['profile', 'email']
    ]);
        
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
      'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        dd($user);
        // Use these details to create a new profile
        printf('Hello %s!', $user->getName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...' . $e->getMessage());
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}

echo '<pre>';
// Use this to interact with an API on the users behalf
var_dump($token->getToken());
# string(217) "CAADAppfn3msBAI7tZBLWg...

// Number of seconds until the access token will expire, and need refreshing
var_dump($token->getExpires());
# int(1436825866)
echo '</pre>';
```

### The RooiOntmoetUser Entity

When using the `getResourceOwner()` method to obtain the user node, it will be returned as a `RooiOntmoetUser` entity.

```php
$user = $provider->getResourceOwner($token);

$id = $user->getId();
var_dump($id);
# string(1) "4"

$name = $user->getName();
var_dump($name);
# string(15) "First Last"

# Requires the "email" scope
$email = $user->getEmail();
var_dump($email);
# string(15) "user@example.com"
```

You can also get all the data from the User node as a plain-old PHP array with `toArray()`.

```php
$userData = $user->toArray();
```

### Client Credentials Flow

You can use the Client Credentials Flow to make a direct request within your application, without asking for permission.
This will operate on behalf of your own Client and is only available when you have access to the given scopes.

```php
// Create Provider
$provider = new RooiOntmoet\OAuth2\Client\Provider\RooiOntmoet([
  'clientId' => 'my-client-id',
  'clientSecret' => 'my-client-secret',
]);

try {
    // Try to get an access token using the client credentials grant.
    $token = $provider->getAccessToken('client_credentials', [
      'scope' => 'allusers',
    ]);

    $request = $provider->getAuthenticatedRequest('GET', $provider->baseResourceUrl . '/users', $token);
    $response = $provider->getHttpClient()->send($request);
    $result = json_decode($response->getBody(), true);

    dd($result);

} catch (\Exception $e) {

    // Failed to get the access token
    exit($e->getMessage());

}
```

### Laravel Socialite Driver

You can use the Socialite provider to enable easy OAuth in Laravel. Just add the driver in your ServiceProvider.

```php
$socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
$socialite->extend(
    'rooiontmoet',
    function ($app) use ($socialite) {
        $config = [
              'client_id' => 'client1id',
              'client_secret' => 'client1secret',
              'redirect' => '',
          ];

        $provider = $socialite->buildProvider('RooiOntmoet\OAuth2\Client\Socialite\RooiOntmoet', $config);
        $provider = $provider->scopes(['public', 'email']);

        return $provider;
    }
);
```
