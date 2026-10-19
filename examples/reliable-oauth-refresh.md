# Reliable OAuth token refresh

Lexware normally rotates the refresh token on every refresh. Only the latest stored pair may be used. HTTP 400 alone does not tell whether a connection needs reauthorization. See the official [refresh best practices](https://developers.lexware.io/partner/docs/#security-and-authentication-token-refresh-best-practices) and [OAuth error responses](https://developers.lexware.io/partner/docs/#error-codes-authorization-and-connection-error-responses-oauth2-error-responses).

## Configure shared locks and storage

All workers handling the same connection must use the same authoritative token storage, cache namespace, lock store and connection identity. Use a shared Redis or database cache store with Laravel atomic locks. An `array` store is process-local. Do not use array or file stores for refresh coordination between workers or hosts. A file store on one filesystem does not provide the required shared deployment guarantee. The array store is suitable for deterministic overlap tests within a process.

```dotenv
LEXWARE_OFFICE_OAUTH2_BASE_URL=https://app.lexware.de
LEXWARE_OFFICE_OAUTH2_LOCK_STORE=redis
LEXWARE_OFFICE_TOKEN_CACHE_STORE=redis
```

Use the authorization server URL assigned to your OAuth client and environment. For example, sandbox OAuth uses `https://app.lexware-sandbox.de`. `lexware-office.base_url` remains the resource API URL. The new `oauth2.base_url` defaults to `null` to preserve the old resource URL fallback, removing only a trailing `/v1`. Configure it explicitly for new installations. The existing constructor's fourth argument still specifies the authorization server URL.

The published `config/lexware-office.php` adds:

```php
'oauth2' => [
    'base_url' => env('LEXWARE_OFFICE_OAUTH2_BASE_URL'),
    'refresh' => [
        'lock_store' => env('LEXWARE_OFFICE_OAUTH2_LOCK_STORE'), // null uses cache.default
        'lock_ttl' => 90, // seconds
        'lock_wait' => 10, // seconds, 0 fails immediately if held
        'http_timeout' => 20, // seconds per token request, no HTTP retry
        'minimum_remaining_seconds' => 30,
    ],
    'token_storage' => [
        'driver' => 'cache',
        'cache_store' => env('LEXWARE_OFFICE_TOKEN_CACHE_STORE'),
        'cache_key' => 'lexware_office_token',
        'refresh_token_ttl' => 31536000,
        'database_table' => 'lexware_tokens',
        'user_column' => 'user_id',
    ],
],
```

Keep the lock TTL longer than the entire HTTP and local persistence operation, including storage timeouts and both local write attempts. The service rejects a TTL shorter than `http_timeout + 10`. A finite lease cannot protect a worker paused longer than its TTL. Configure bounded storage timeouts and a suitable margin for your deployment. Never delete or force-release an active refresh lock. A waiter timing out does not release the holder's lock.

The cache storage retains the pair for at least `refresh_token_ttl` seconds from the last write, independently of access token expiry. This TTL is local retention, not a promise about the token's server-side validity. Cache eviction, flushes and expiry can still remove credentials. Use durable encrypted storage for important connections. `DatabaseTokenStorage` uses the query builder and **does not encrypt tokens**. Applications should supply their own encrypted model-backed storage when required.

## Refresh guarantees and entry points

`getValidAccessToken(?int $minimumRemainingSeconds = null)`, `refreshToken()` and 401 recovery use one refresh core. On-demand checks default to 30 seconds remaining. A scheduler can request a larger window:

```php
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;

$oauth = LexwareOfficeFactory::createOAuth2Service($connectionId);
$token = $oauth->getValidAccessToken(10 * 60);
// The returned pair has already been stored. Do not store a stale copy later.
```

The client wrapper also accepts `getValidOAuth2Token(10 * 60)`. A missing stored token returns `null`. An expired token without a refresh token returns `null`; an explicit refresh without stored refresh credentials throws an operational exception.

Before contacting Lexware, the core acquires an owned lock and reloads storage. If another worker already refreshed, the waiter reuses the new, unexpired pair. Otherwise it checks the lifetime again and refreshes only when needed. An explicit `refreshToken()` forces rotation unless the pair changed while waiting. Its old optional `$refreshToken` argument is deprecated and **ignored**. It cannot seed storage or bypass the lock. Install initial credentials through your storage first.

The lock key combines the application environment, authorization server, client identity and stable storage identity. Built-in storages provide connection identities. For an existing custom storage, supply the optional sixth constructor argument `connectionKey`:

```php
$oauth = new LexwareOAuth2Service(
    $clientId, $clientSecret, $redirectUri, $authorizationServer, $scopes,
    connectionKey: 'connection:'.$connectionId,
);
$oauth->setTokenStorage($encryptedStorage);
```

Use the same key across every worker and entry point for that connection. Without a key or optional storage capability, legacy custom storages share one conservative lock per client and environment. This serializes unrelated connections but avoids inventing unstable identities. If environments share a cache or database, namespace the token records as well as the locks.

After a resource API 401, the client compares the rejected access token with current storage under this same lock. It reuses a replacement or refreshes the current pair, rebuilds the Authorization header and retries the resource request once. A second 401 is surfaced. Refresh failures propagate as structured OAuth exceptions rather than being hidden behind the original 401.

Token requests use HTTP Basic client authentication, disable redirects and perform no automatic HTTP retries. A successful response must include nonempty string `access_token` and `refresh_token` fields and a positive integer `expires_in`. An optional `token_type` must be `Bearer`; omission retains the existing Bearer default. The pair is persisted before returning or making another resource request.

## Structured errors

`LexwareOAuthException` extends the existing `LexwareOfficeApiException`, so existing catch blocks keep working. It provides `getStatusCode()` (0 when no HTTP response exists), `oauthError`, `oauthErrorDescription`, `getRetryAfter()` in seconds, and `category` as `OAuthErrorCategory`. `context()` contains only these safe diagnostics.

| Category | Meaning and action |
| --- | --- |
| `InvalidGrant` | The token endpoint rejected the current grant. Reauthorization is required. If storage changed in flight, the core reuses a valid replacement instead. |
| `Transient` | HTTP 429, 5xx, lock contention timeout, or a transport failure proven to occur before sending (DNS resolution or connection refusal). Back off and respect Retry-After. The package does not retry the token request. |
| `OutcomeUnknown` | A timeout or connection loss may have happened after sending, or local credentials changed before persistence. Rotation may already have occurred. Stop automatic refresh attempts for this connection and investigate. |
| `ClientConfiguration` | Invalid or deactivated client, malformed request, unknown error response, missing credentials or configuration problem. Resolve operationally; do not ask the customer to reconnect based on HTTP 400 alone. A malformed success response may already have rotated credentials, so investigate before another attempt. |

For `unauthorized_client`, stop attempts and contact Lexware support. A deactivated client's refresh tokens remain usable after reactivation according to the official documentation. Do not delete them.

```php
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOAuthException;
use Pirabyte\LaravelLexwareOffice\OAuth2\OAuthErrorCategory;

try {
    $token = $oauth->getValidAccessToken(600);
} catch (LexwareOAuthException $e) {
    if ($e->category === OAuthErrorCategory::InvalidGrant) {
        // Request reauthorization for this connection.
    } else {
        // Pause or reschedule according to the category and getRetryAfter().
    }
    logger()->warning('OAuth refresh failed', $e->context());
}
```

Error codes are allowlisted. Untrusted server descriptions are replaced with short canonical descriptions, rather than attempting to redact arbitrary echoed credentials. Unknown codes and `error_uri` are omitted. Raw response bodies, transport exceptions, request objects and Authorization headers are never attached to these exceptions. Exception serialization and debug output include only safe fields. Keep `zend.exception_ignore_args=On` in production as an additional general protection for application stack traces. Never log token objects or the result of `takeToken()`.

## Persistence failures after rotation

If storage throws after a valid token response, the service retries the **local** write once with the same received pair. It never calls Lexware a second time. A write that succeeded before its acknowledgement failed is detected by reloading storage.

After both local attempts fail, `LexwareTokenPersistenceException` reports `OutcomeUnknown`. Suspend automatic refresh work for that connection immediately. Its one-time `takeToken()` retrieves the pair from an in-memory `WeakMap` outside exception properties, serialization and log context. Recovery is available only while the original exception remains alive in the same PHP process. A serialized exception deliberately has no recovery pair. A process crash can lose it.

```php
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareTokenPersistenceException;

try {
    $token = $oauth->refreshToken();
} catch (LexwareTokenPersistenceException $e) {
    $receivedPair = $e->takeToken();
    // Securely preserve the pair while repairing storage. Do not log or serialize $receivedPair.
    // Recover under the same connection lock and compare against the pre-refresh storage state.
    // Never overwrite credentials that a later authorization or refresh already replaced.
    throw $e;
}
```

This is an explicit emergency recovery mechanism, not an automatic retry policy or a durable recovery queue. The package does not persist failure markers on the application's behalf. The application must suspend later calls after `OutcomeUnknown` or a persistence failure; calling refresh again may reuse a consumed refresh token.

## Custom storage and atomic writes

The original three-method `LexwareTokenStorage` interface is unchanged. Its implementations remain usable through `setTokenStorage()`. The refresh core checks current credentials before a legacy write while holding the shared lock. This protects cooperating refresh callers, but cannot make an external writer or an expired lease atomic.

Implement the additive `RefreshTokenStorage` interface for stronger protection:

```php
interface RefreshTokenStorage extends LexwareTokenStorage
{
    public function getRefreshLockKey(): string;
    public function storeTokenIfCurrent(LexwareAccessToken $token, string $refreshToken): bool;
}
```

`getRefreshLockKey()` identifies the stable storage record. `storeTokenIfCurrent()` must atomically compare the currently stored refresh token with the one used for refresh and replace the **entire** token pair only on a match. Return `false` when the record changed or was deleted. Throw on a persistence failure. A failed comparison is surfaced without overwriting newer credentials. The built-in cache storage uses an owned write lock around comparisons and writes; database storage uses a conditional SQL update. Initial authorization can still use `storeToken()` to install a pair.

For encrypted Eloquent attributes, compare the decrypted value inside a database transaction with a row lock. For example, add these methods to your existing model-backed storage, which already implements `storeToken`, `getToken` and `clearToken`:

```php
public function getRefreshLockKey(): string
{
    return 'encrypted-connection:'.$this->connectionId;
}

public function storeTokenIfCurrent(
    #[\SensitiveParameter] LexwareAccessToken $token,
    #[\SensitiveParameter] string $refreshToken,
): bool {
    return DB::transaction(function () use ($token, $refreshToken): bool {
        $connection = OAuthConnection::query()->lockForUpdate()->find($this->connectionId);
        if (! $connection || $connection->refresh_token !== $refreshToken) {
            return false;
        }

        $connection->access_token = $token->getAccessToken();
        $connection->refresh_token = $token->getRefreshToken();
        $connection->token_type = $token->getTokenType();
        $connection->expires_in = $token->getExpiresIn();
        $connection->token_created_at = $token->getCreatedAt();
        $connection->scopes = $token->getScopes();
        $connection->saveOrFail();

        return true;
    });
}
```

Here `OAuthConnection` is an application model with encrypted token casts and hidden credential attributes. Use its actual database connection for the transaction. Implement reads from the authoritative database without a stale relationship or cached model instance. Return only after the transaction commits durably. Do not wrap refresh in a larger transaction that can roll back after the service returns. Ensure all external writers follow the same concurrency rules. The package does not require a particular application model or schema migration.

## Upgrade notes for the next version

- Configure a shared lock-capable store on every worker and review its TTL and storage timeouts. Cache token storage now also requires atomic locks for writes.
- Merge the new configuration keys into published configuration. Both the existing string `oauth2.token_storage` driver form and the published array form remain supported by the factory.
- Set `oauth2.base_url` explicitly to your authorization server. Existing `base_url`, credentials, storage keys and direct constructor calls keep working.
- `refreshToken($suppliedToken)` no longer uses a caller-supplied token, and fails if no stored refresh credentials exist. Migrate callers to authoritative storage and `refreshToken()`.
- The default proactive window changes from five minutes to 30 seconds. Pass a larger minimum lifetime for schedulers. Subclasses overriding these methods must accept their new optional parameters.
- Catch `LexwareOAuthException` for category-based handling. A resource 401 can now surface the refresh exception. Do not parse Guzzle error text or infer reauthorization from HTTP 400.
- Token responses are validated before storing. Optional Bearer type defaults remain compatible, but malformed responses no longer create partial credentials.
- Cache retention is one year by default, independent of access token expiry. Review your application's retention and durable storage needs.
- Add `RefreshTokenStorage` to custom storage when atomic stale-write protection is required. The original interface is unchanged. No package schema change is needed.
- Handle uncertain outcomes and persistence failures operationally before retrying. The package does not automatically disable a connection or clear its credentials.
