<?php

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

use Illuminate\Support\Facades\DB;

class DatabaseTokenStorage implements LexwareTokenStorage
{
    protected string $table;

    protected string $userColumn;

    protected mixed $userId;

    public function __construct(
        mixed $userId,
        string $table = 'lexware_tokens',
        string $userColumn = 'user_id'
    ) {
        $this->userId = $userId;
        $this->table = $table;
        $this->userColumn = $userColumn;
    }

    /**
     * Store an access token in database
     */
    public function storeToken(LexwareAccessToken $token): void
    {
        $data = [
            $this->userColumn => $this->userId,
            'access_token' => $token->getAccessToken(),
            'token_type' => $token->getTokenType(),
            'expires_in' => $token->getExpiresIn(),
            'refresh_token' => $token->getRefreshToken(),
            'scopes' => json_encode($token->getScopes()),
            'created_at' => $token->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => now(),
        ];

        DB::table($this->table)->updateOrInsert(
            [$this->userColumn => $this->userId],
            $data
        );
    }

    /**
     * Retrieve the stored access token from database
     */
    public function getToken(): ?LexwareAccessToken
    {
        $record = DB::table($this->table)
            ->where($this->userColumn, $this->userId)
            ->first();

        if (! $record) {
            return null;
        }

        try {
            return new LexwareAccessToken(
                $record->access_token,
                $record->token_type ?? 'Bearer',
                $record->expires_in ?? 3600,
                $record->refresh_token,
                json_decode($record->scopes ?? '[]', true) ?: [],
                new \DateTime($record->created_at)
            );
        } catch (\Exception $e) {
            // If token data is corrupted, clear it
            $this->clearToken();

            return null;
        }
    }

    /**
     * Clear the stored token from database
     */
    public function clearToken(): void
    {
        DB::table($this->table)
            ->where($this->userColumn, $this->userId)
            ->delete();
    }
}
