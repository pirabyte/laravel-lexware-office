# OAuth2 Authentication Example

This package provides complete OAuth2 support for Lexware Office API authentication with per-user instances, including automatic token refresh and management.

## Configuration

### 1. Environment Variables

Add these to your `.env` file:

```env
# Basic API Configuration
LEXWARE_OFFICE_BASE_URL=https://api.lexoffice.de/v1
LEXWARE_OFFICE_API_KEY=your_fallback_api_key

# OAuth2 Configuration
LEXWARE_OFFICE_OAUTH2_CLIENT_ID=your_client_id
LEXWARE_OFFICE_OAUTH2_CLIENT_SECRET=your_client_secret
LEXWARE_OFFICE_OAUTH2_REDIRECT_URI=https://your-app.com/auth/lexware/callback
```

### 2. Publish Configuration and Migration

```bash
# Publish everything (config + migration)
php artisan vendor:publish --provider="Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider" --tag="lexware-office"

# Or publish separately:
php artisan vendor:publish --provider="Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider" --tag="lexware-office-config"
php artisan vendor:publish --provider="Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider" --tag="lexware-office-migration"
```

### 3. Run Migration

```bash
php artisan migrate
```

## Usage Examples

### 1. Per-User OAuth2 Setup (Recommended)

#### Step 1: Redirect User to Lexware

```php
<?php

use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;

class AuthController extends Controller
{
    public function redirectToLexware(Request $request)
    {
        $user = $request->user();
        
        // Create OAuth2 service for this user
        $oauth2Service = LexwareOfficeFactory::createOAuth2Service($user->id);
        
        $authUrl = $oauth2Service->getAuthorizationUrl();
        
        // Store state in session for verification
        session(['lexware_oauth_state' => $authUrl->getState()]);
        
        return redirect($authUrl->getUrl());
    }
}
```

#### Step 2: Handle Callback

```php
<?php

class AuthController extends Controller
{
    public function handleCallback(Request $request)
    {
        $user = $request->user();
        $code = $request->get('code');
        $state = $request->get('state');
        
        // Verify state parameter
        if ($state !== session('lexware_oauth_state')) {
            abort(400, 'Invalid state parameter');
        }
        
        try {
            // Create OAuth2 service for this user
            $oauth2Service = LexwareOfficeFactory::createOAuth2Service($user->id);
            
            // Exchange code for token (automatically stored in database)
            $token = $oauth2Service->exchangeCodeForToken($code, $state);
            
            return redirect()->route('dashboard')->with('success', 'Connected to Lexware!');
            
        } catch (LexwareOfficeApiException $e) {
            return redirect()->route('auth.error')->with('error', $e->getMessage());
        }
    }
}
```

### 2. Making Authenticated API Calls

#### Per-User Instance (Recommended Approach)

```php
<?php

use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;
use Pirabyte\LaravelLexwareOffice\Models\Contact;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Create Lexware instance for this user with automatic OAuth2
        $lexware = LexwareOfficeFactory::forUser($user->id);
        
        // The instance automatically:
        // 1. Checks if token is valid
        // 2. Refreshes token if needed  
        // 3. Retries request if auth fails
        
        $contacts = $lexware->contacts()->all();
        
        return view('contacts.index', compact('contacts'));
    }
    
    public function store(Request $request)
    {
        $user = $request->user();
        $lexware = LexwareOfficeFactory::forUser($user->id);
        
        $contact = Contact::createPerson(
            $request->first_name,
            $request->last_name
        );
        
        // Automatic token management per user
        $createdContact = $lexware->contacts()->create($contact);
        
        return redirect()->route('contacts.show', $createdContact->getId());
    }
}
```

#### Service Class Approach

```php
<?php

use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;

class UserLexwareService
{
    protected $user;
    protected $lexwareClient;
    
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->lexwareClient = LexwareOfficeFactory::forUser($user->id);
    }
    
    public function getContacts()
    {
        return $this->lexwareClient->contacts()->all();
    }
    
    public function createContact(Contact $contact)
    {
        return $this->lexwareClient->contacts()->create($contact);
    }
    
    public function getVouchers()
    {
        return $this->lexwareClient->vouchers()->all();
    }
    
    public function isAuthenticated(): bool
    {
        $oauth2Service = $this->lexwareClient->oauth2();
        if (!$oauth2Service) {
            return false;
        }
        
        $token = $oauth2Service->getValidAccessToken();
        return $token !== null;
    }
}
```

### 3. Manual Token Management

```php
<?php

use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;

class TokenController extends Controller
{
    public function getTokenInfo(Request $request)
    {
        $user = $request->user();
        $oauth2Service = LexwareOfficeFactory::createOAuth2Service($user->id);
        
        $token = $oauth2Service->getValidAccessToken();
        
        if (!$token) {
            return response()->json(['authenticated' => false]);
        }
        
        return response()->json([
            'authenticated' => true,
            'expires_at' => $token->getExpiresAt()->format('c'),
            'scopes' => $token->getScopes(),
            'expires_in' => $token->getRemainingTime(),
        ]);
    }
    
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        
        try {
            $oauth2Service = LexwareOfficeFactory::createOAuth2Service($user->id);
            $token = $oauth2Service->refreshToken();
            
            return response()->json([
                'success' => true,
                'expires_at' => $token->getExpiresAt()->format('c'),
            ]);
            
        } catch (LexwareOfficeApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 401);
        }
    }
    
    public function logout(Request $request)
    {
        $user = $request->user();
        $oauth2Service = LexwareOfficeFactory::createOAuth2Service($user->id);
        
        $oauth2Service->revokeToken();
        
        return redirect()->route('home')->with('success', 'Logged out successfully');
    }
}
```

### 4. Using Static API Keys (Alternative)

For simpler setups or when OAuth2 is not needed:

```php
<?php

use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;

class SimpleContactController extends Controller
{
    public function index()
    {
        // Use static API key instead of OAuth2
        $lexware = LexwareOfficeFactory::withApiKey('your-api-key');
        
        $contacts = $lexware->contacts()->all();
        
        return view('contacts.index', compact('contacts'));
    }
}
```

### 5. Middleware for Authentication

Create middleware to ensure users are authenticated:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;

class EnsureLexwareAuthenticated
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $oauth2Service = LexwareOfficeFactory::createOAuth2Service($user->id);
        $token = $oauth2Service->getValidAccessToken();
        
        if (!$token) {
            return redirect()->route('auth.lexware')
                ->with('error', 'Please connect your Lexware account first.');
        }
        
        return $next($request);
    }
}
```

## Error Handling

The package provides comprehensive error handling:

```php
<?php

use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $lexware = LexwareOfficeFactory::forUser($user->id);
        
        try {
            $contacts = $lexware->contacts()->all();
            return view('contacts.index', compact('contacts'));
            
        } catch (LexwareOfficeApiException $e) {
            if ($e->isAuthError()) {
                // Token expired or invalid - redirect to auth
                return redirect()->route('auth.lexware')
                    ->with('error', 'Please reconnect your Lexware account.');
            }
            
            if ($e->isRateLimitError()) {
                // Rate limit exceeded
                $retryAfter = $e->getRetryAfter();
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $retryAfter
                ], 429);
            }
            
            // Other API errors
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}
```

## Security Considerations

1. **PKCE**: The package automatically uses PKCE (Proof Key for Code Exchange) for enhanced security
2. **State Parameter**: Always verify the state parameter in callbacks
3. **Token Storage**: Use database storage for production multi-user apps
4. **HTTPS**: Always use HTTPS for your redirect URIs
5. **Scopes**: Request only the minimum required scopes

## Benefits

✅ **Per-User Instances** - Each user gets their own Lexware client instance  
✅ **Automatic Token Refresh** - No manual token management required  
✅ **PKCE Security** - Follows OAuth2 security best practices  
✅ **Laravel Integration** - Seamless config, database, and caching  
✅ **Publishable Migrations** - Easy database setup with `php artisan vendor:publish`  
✅ **Factory Pattern** - Clean, testable code with `LexwareOfficeFactory`  
✅ **Multiple Storage Options** - Database or cache token storage per user  
✅ **Error Handling** - Comprehensive exception handling  
✅ **Production Ready** - Handles all edge cases and error scenarios

## Installation Summary

```bash
# 1. Install the package
composer require pirabyte/laravel-lexware-office

# 2. Publish config and migration
php artisan vendor:publish --provider="Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider" --tag="lexware-office"

# 3. Configure your .env
LEXWARE_OFFICE_OAUTH2_CLIENT_ID=your_client_id
LEXWARE_OFFICE_OAUTH2_CLIENT_SECRET=your_client_secret
LEXWARE_OFFICE_OAUTH2_REDIRECT_URI=https://your-app.com/auth/lexware/callback

# 4. Run migration
php artisan migrate

# 5. Use in your controllers
$lexware = LexwareOfficeFactory::forUser($user->id);
$contacts = $lexware->contacts()->all();
```