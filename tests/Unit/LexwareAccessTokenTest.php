<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\OAuth2\LexwareAccessToken;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class LexwareAccessTokenTest extends TestCase
{
    public function test_creates_token_with_basic_properties()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile', 'contacts']
        );

        $this->assertEquals('access_token_123', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
        $this->assertEquals('refresh_token_456', $token->getRefreshToken());
        $this->assertEquals(['profile', 'contacts'], $token->getScopes());
    }

    public function test_creates_token_with_custom_created_at()
    {
        $createdAt = new \DateTime('2023-01-01 12:00:00');
        
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile'],
            $createdAt
        );

        $this->assertEquals($createdAt, $token->getCreatedAt());
    }

    public function test_uses_current_time_when_created_at_not_provided()
    {
        $beforeCreation = new \DateTime();
        
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600
        );
        
        $afterCreation = new \DateTime();

        $this->assertGreaterThanOrEqual($beforeCreation, $token->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $token->getCreatedAt());
    }

    public function test_calculates_expiry_time_correctly()
    {
        $createdAt = new \DateTime('2023-01-01 12:00:00');
        
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600, // 1 hour
            null,
            [],
            $createdAt
        );

        $expectedExpiry = (clone $createdAt)->add(new \DateInterval('PT3600S'));
        $this->assertEquals($expectedExpiry, $token->getExpiryTime());
    }

    public function test_detects_expired_token()
    {
        $pastTime = (new \DateTime())->sub(new \DateInterval('PT2H')); // 2 hours ago
        
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600, // 1 hour expiry
            null,
            [],
            $pastTime
        );

        $this->assertTrue($token->isExpired());
    }

    public function test_detects_valid_token()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600 // 1 hour from now
        );

        $this->assertFalse($token->isExpired());
    }

    public function test_detects_token_expiring_soon()
    {
        $recentTime = (new \DateTime())->sub(new \DateInterval('PT3500S')); // 3500 seconds ago
        
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600, // 1 hour expiry, so only 100 seconds left
            null,
            [],
            $recentTime
        );

        $this->assertTrue($token->isExpiringSoon());
        $this->assertTrue($token->isExpiringSoon(200)); // Custom buffer of 200 seconds
        $this->assertFalse($token->isExpiringSoon(50)); // Custom buffer of 50 seconds
    }

    public function test_detects_token_not_expiring_soon()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600 // Full hour remaining
        );

        $this->assertFalse($token->isExpiringSoon());
    }

    public function test_validates_scope_access()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            null,
            ['profile', 'contacts', 'invoices']
        );

        $this->assertTrue($token->hasScope('profile'));
        $this->assertTrue($token->hasScope('contacts'));
        $this->assertTrue($token->hasScope('invoices'));
        $this->assertFalse($token->hasScope('admin'));
        $this->assertFalse($token->hasScope(''));
    }

    public function test_validates_multiple_scopes()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            null,
            ['profile', 'contacts', 'invoices']
        );

        $this->assertTrue($token->hasScopes(['profile', 'contacts']));
        $this->assertTrue($token->hasScopes(['profile']));
        $this->assertFalse($token->hasScopes(['profile', 'admin']));
        $this->assertFalse($token->hasScopes(['admin', 'billing']));
    }

    public function test_handles_empty_scopes()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            null,
            []
        );

        $this->assertFalse($token->hasScope('profile'));
        $this->assertFalse($token->hasScopes(['profile']));
        $this->assertTrue($token->hasScopes([])); // Empty array should return true
    }

    public function test_generates_authorization_header()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600
        );

        $this->assertEquals('Bearer access_token_123', $token->getAuthorizationHeader());
    }

    public function test_generates_authorization_header_with_custom_token_type()
    {
        $token = new LexwareAccessToken(
            'access_token_123',
            'Custom',
            3600
        );

        $this->assertEquals('Custom access_token_123', $token->getAuthorizationHeader());
    }

    public function test_serializes_to_json()
    {
        $createdAt = new \DateTime('2023-01-01 12:00:00');
        
        $token = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile', 'contacts'],
            $createdAt
        );

        $json = $token->toJson();
        $data = json_decode($json, true);

        $this->assertEquals('access_token_123', $data['access_token']);
        $this->assertEquals('Bearer', $data['token_type']);
        $this->assertEquals(3600, $data['expires_in']);
        $this->assertEquals('refresh_token_456', $data['refresh_token']);
        $this->assertEquals(['profile', 'contacts'], $data['scopes']);
        $this->assertEquals('2023-01-01T12:00:00+00:00', $data['created_at']);
    }

    public function test_creates_from_json()
    {
        $jsonData = [
            'access_token' => 'access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh_token_456',
            'scopes' => ['profile', 'contacts'],
            'created_at' => '2023-01-01T12:00:00+00:00'
        ];

        $token = LexwareAccessToken::fromJson(json_encode($jsonData));

        $this->assertEquals('access_token_123', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
        $this->assertEquals('refresh_token_456', $token->getRefreshToken());
        $this->assertEquals(['profile', 'contacts'], $token->getScopes());
        $this->assertEquals('2023-01-01T12:00:00+00:00', $token->getCreatedAt()->format('c'));
    }

    public function test_creates_from_json_with_minimal_data()
    {
        $jsonData = [
            'access_token' => 'access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];

        $token = LexwareAccessToken::fromJson(json_encode($jsonData));

        $this->assertEquals('access_token_123', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
        $this->assertNull($token->getRefreshToken());
        $this->assertEquals([], $token->getScopes());
    }

    public function test_creates_from_array()
    {
        $data = [
            'access_token' => 'access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh_token_456',
            'scope' => 'profile contacts' // Space-separated string
        ];

        $token = LexwareAccessToken::fromArray($data);

        $this->assertEquals('access_token_123', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
        $this->assertEquals('refresh_token_456', $token->getRefreshToken());
        $this->assertEquals(['profile', 'contacts'], $token->getScopes());
    }

    public function test_creates_from_array_with_scope_array()
    {
        $data = [
            'access_token' => 'access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => ['profile', 'contacts'] // Array format
        ];

        $token = LexwareAccessToken::fromArray($data);

        $this->assertEquals(['profile', 'contacts'], $token->getScopes());
    }

    public function test_handles_missing_optional_fields_in_array()
    {
        $data = [
            'access_token' => 'access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];

        $token = LexwareAccessToken::fromArray($data);

        $this->assertEquals('access_token_123', $token->getAccessToken());
        $this->assertNull($token->getRefreshToken());
        $this->assertEquals([], $token->getScopes());
    }

    public function test_roundtrip_json_serialization()
    {
        $originalToken = new LexwareAccessToken(
            'access_token_123',
            'Bearer',
            3600,
            'refresh_token_456',
            ['profile', 'contacts']
        );

        $json = $originalToken->toJson();
        $reconstructedToken = LexwareAccessToken::fromJson($json);

        $this->assertEquals($originalToken->getAccessToken(), $reconstructedToken->getAccessToken());
        $this->assertEquals($originalToken->getTokenType(), $reconstructedToken->getTokenType());
        $this->assertEquals($originalToken->getExpiresIn(), $reconstructedToken->getExpiresIn());
        $this->assertEquals($originalToken->getRefreshToken(), $reconstructedToken->getRefreshToken());
        $this->assertEquals($originalToken->getScopes(), $reconstructedToken->getScopes());
        // Allow small time difference for creation time
        $this->assertEquals(
            $originalToken->getCreatedAt()->getTimestamp(),
            $reconstructedToken->getCreatedAt()->getTimestamp(),
            5 // 5 second tolerance
        );
    }
}