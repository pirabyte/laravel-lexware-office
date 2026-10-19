<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\OAuth2;

use Illuminate\Support\Facades\DB;

class DatabaseTokenStorage implements RefreshTokenStorage
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
    private function tokenData(#[\SensitiveParameter] LexwareAccessToken $token): array
    {
        return [
            $this->userColumn => $this->userId,
            'access_token' => $token->getAccessToken(),
            'token_type' => $token->getTokenType(),
            'expires_in' => $token->getExpiresIn(),
            'refresh_token' => $token->getRefreshToken(),
            'scopes' => json_encode($token->getScopes()),
            'created_at' => $token->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => now(),
        ];

    }

    public function getRefreshLockKey(): string
    {
        return 'database:'.DB::connection()->getName().':'.DB::connection()->getDatabaseName()
            .':'.$this->table.':'.$this->userColumn.':'.$this->userId;
    }

    public function storeToken(#[\SensitiveParameter] LexwareAccessToken $token): void
    {
        DB::table($this->table)->updateOrInsert(
            [$this->userColumn => $this->userId],
            $this->tokenData($token)
        );
    }

    public function storeTokenIfCurrent(
        #[\SensitiveParameter] LexwareAccessToken $token,
        #[\SensitiveParameter] string $refreshToken
    ): bool {
        return DB::table($this->table)
            ->where($this->userColumn, $this->userId)
            ->where('refresh_token', $refreshToken)
            ->update($this->tokenData($token)) === 1;
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
                (int) ($record->expires_in ?? 3600),
                $record->refresh_token,
                json_decode($record->scopes ?? '[]', true) ?: [],
                new \DateTime($record->created_at)
            );
        } catch (\Exception $e) {
            // Do not delete credentials that another worker may have replaced.
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
