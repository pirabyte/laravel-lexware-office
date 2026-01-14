<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Pirabyte\LaravelLexwareOffice\Exceptions\LexwareOfficeApiException;
use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeFactory;
use Pirabyte\LaravelLexwareOffice\OAuth2\CacheTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\DatabaseTokenStorage;
use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareOAuth2Service;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class LexwareOfficeFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up basic config for tests
        Config::set('lexware-office.api_key', 'test_api_key');
        Config::set('lexware-office.base_url', 'https://api.lexoffice.de');
        Config::set('lexware-office.oauth2.client_id', 'test_client_id');
        Config::set('lexware-office.oauth2.client_secret', 'test_client_secret');
        Config::set('lexware-office.oauth2.redirect_uri', 'https://example.com/callback');
        Config::set('lexware-office.oauth2.scopes', ['profile', 'contacts']);
        Config::set('lexware-office.oauth2.token_storage.driver', 'cache');
    }

    public function test_creates_instance_for_user_with_cache_storage()
    {
        Config::set('lexware-office.oauth2.token_storage.driver', 'cache');

        $lexware = LexwareOfficeFactory::forUser(123);

        $this->assertInstanceOf(LexwareOffice::class, $lexware);

        // Verify rate limiting key is set correctly
        $this->assertEquals('lexware_office_api_user_123', $lexware->getRateLimitKey());
    }

    public function test_creates_instance_for_user_with_database_storage()
    {
        Config::set('lexware-office.oauth2.token_storage.driver', 'database');

        $lexware = LexwareOfficeFactory::forUser(456);

        $this->assertInstanceOf(LexwareOffice::class, $lexware);

        // Check that OAuth2 service is configured with database storage
        $oauth2Service = $lexware->getOAuth2Service();

        $this->assertInstanceOf(LexwareOAuth2Service::class, $oauth2Service);
    }

    public function test_creates_oauth2_service_for_user_with_cache_storage()
    {
        Config::set('lexware-office.oauth2.token_storage.driver', 'cache');

        $oauth2Service = LexwareOfficeFactory::createOAuth2Service(789);

        $this->assertInstanceOf(LexwareOAuth2Service::class, $oauth2Service);

        // Verify token storage is configured correctly
        $reflection = new \ReflectionClass($oauth2Service);
        $storageProperty = $reflection->getProperty('tokenStorage');
        $storage = $storageProperty->getValue($oauth2Service);

        $this->assertInstanceOf(CacheTokenStorage::class, $storage);

        // Check the storage key
        $storageReflection = new \ReflectionClass($storage);
        $keyProperty = $storageReflection->getProperty('cacheKey');
        $this->assertEquals('lexware_office_token_user_789', $keyProperty->getValue($storage));
    }

    public function test_creates_oauth2_service_for_user_with_database_storage()
    {
        Config::set('lexware-office.oauth2.token_storage.driver', 'database');

        $oauth2Service = LexwareOfficeFactory::createOAuth2Service(101112);

        $this->assertInstanceOf(LexwareOAuth2Service::class, $oauth2Service);

        // Verify token storage is configured correctly
        $reflection = new \ReflectionClass($oauth2Service);
        $storageProperty = $reflection->getProperty('tokenStorage');
        $storage = $storageProperty->getValue($oauth2Service);

        $this->assertInstanceOf(DatabaseTokenStorage::class, $storage);

        // Check the user ID
        $storageReflection = new \ReflectionClass($storage);
        $userIdProperty = $storageReflection->getProperty('userId');
        $this->assertEquals(101112, $userIdProperty->getValue($storage));
    }

    public function test_throws_exception_for_invalid_token_storage_type()
    {
        Config::set('lexware-office.oauth2.token_storage.driver', 'invalid_storage');

        $this->expectException(LexwareOfficeApiException::class);
        $this->expectExceptionMessage('Invalid token storage type: invalid_storage');

        LexwareOfficeFactory::createOAuth2Service(123);
    }

    public function test_creates_instance_with_api_key()
    {
        $lexware = LexwareOfficeFactory::withApiKey('custom_api_key');

        $this->assertInstanceOf(LexwareOffice::class, $lexware);

        // Verify API key is set correctly
        $this->assertEquals('custom_api_key', $lexware->getApiKey());
    }

    public function test_creates_instance_with_api_key_and_custom_base_url()
    {
        $lexware = LexwareOfficeFactory::withApiKey('custom_api_key', 'https://custom.api.url');

        $this->assertInstanceOf(LexwareOffice::class, $lexware);

        // Verify base URL is set correctly
        $this->assertEquals('https://custom.api.url', $lexware->getBaseUrl());
    }

    public function test_uses_default_rate_limit_key_for_api_key_instances()
    {
        $lexware = LexwareOfficeFactory::withApiKey('custom_api_key');

        $this->assertEquals('lexware_office_api', $lexware->getRateLimitKey());
    }

    public function test_user_instances_have_isolated_rate_limiting()
    {
        $lexware1 = LexwareOfficeFactory::forUser(111);
        $lexware2 = LexwareOfficeFactory::forUser(222);

        $key1 = $lexware1->getRateLimitKey();
        $key2 = $lexware2->getRateLimitKey();

        $this->assertEquals('lexware_office_api_user_111', $key1);
        $this->assertEquals('lexware_office_api_user_222', $key2);
        $this->assertNotEquals($key1, $key2);
    }

    public function test_creates_instances_with_config_fallbacks()
    {
        // Clear config to test fallbacks
        Config::set('lexware-office.oauth2.client_id', null);
        Config::set('lexware-office.oauth2.client_secret', null);
        Config::set('lexware-office.oauth2.redirect_uri', null);
        Config::set('lexware-office.oauth2.scopes', null);

        // Should still create instance but OAuth2 might not work
        $lexware = LexwareOfficeFactory::forUser(333);
        $this->assertInstanceOf(LexwareOffice::class, $lexware);
    }

    public function test_oauth2_service_configuration_matches_config()
    {
        Config::set('lexware-office.oauth2.client_id', 'config_client_id');
        Config::set('lexware-office.oauth2.client_secret', 'config_client_secret');
        Config::set('lexware-office.oauth2.redirect_uri', 'https://config.callback.com');
        Config::set('lexware-office.oauth2.scopes', ['custom', 'scopes']);
        Config::set('lexware-office.base_url', 'https://config.api.url');

        $oauth2Service = LexwareOfficeFactory::createOAuth2Service(444);

        $reflection = new \ReflectionClass($oauth2Service);

        $clientIdProperty = $reflection->getProperty('clientId');
        $this->assertEquals('config_client_id', $clientIdProperty->getValue($oauth2Service));

        $clientSecretProperty = $reflection->getProperty('clientSecret');
        $this->assertEquals('config_client_secret', $clientSecretProperty->getValue($oauth2Service));

        $redirectUriProperty = $reflection->getProperty('redirectUri');
        $this->assertEquals('https://config.callback.com', $redirectUriProperty->getValue($oauth2Service));

        $scopesProperty = $reflection->getProperty('scopes');
        $this->assertEquals(['custom', 'scopes'], $scopesProperty->getValue($oauth2Service));

        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $this->assertEquals('https://config.api.url', $baseUrlProperty->getValue($oauth2Service));
    }

    public function test_multiple_calls_for_same_user_return_different_instances()
    {
        $lexware1 = LexwareOfficeFactory::forUser(555);
        $lexware2 = LexwareOfficeFactory::forUser(555);

        // Should be different instances (not singletons)
        $this->assertNotSame($lexware1, $lexware2);

        // But should have same configuration
        $this->assertEquals(
            $lexware1->getRateLimitKey(),
            $lexware2->getRateLimitKey()
        );
    }

    public function test_handles_string_user_ids()
    {
        $lexware = LexwareOfficeFactory::forUser('user_abc_123');

        $this->assertEquals('lexware_office_api_user_user_abc_123', $lexware->getRateLimitKey());
    }

    public function test_oauth2_service_handles_string_user_ids()
    {
        Config::set('lexware-office.oauth2.token_storage.driver', 'cache');

        $oauth2Service = LexwareOfficeFactory::createOAuth2Service('user_xyz_456');

        $reflection = new \ReflectionClass($oauth2Service);
        $storageProperty = $reflection->getProperty('tokenStorage');
        $storage = $storageProperty->getValue($oauth2Service);

        $storageReflection = new \ReflectionClass($storage);
        $keyProperty = $storageReflection->getProperty('cacheKey');
        $this->assertEquals('lexware_office_token_user_user_xyz_456', $keyProperty->getValue($storage));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
