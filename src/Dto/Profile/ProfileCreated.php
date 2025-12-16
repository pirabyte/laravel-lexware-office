<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Profile;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class ProfileCreated implements Dto
{
    public function __construct(
        public string $userId,
        public string $userName,
        public string $userEmail,
        public DateTimeImmutable $date,
    ) {
        Assert::nonEmptyString($this->userId, 'ProfileCreated.userId must be non-empty');
        Assert::nonEmptyString($this->userName, 'ProfileCreated.userName must be non-empty');
        Assert::nonEmptyString($this->userEmail, 'ProfileCreated.userEmail must be non-empty');
    }
}


