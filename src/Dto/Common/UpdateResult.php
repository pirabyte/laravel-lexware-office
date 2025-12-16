<?php

declare(strict_types=1);

namespace Pirabyte\LaravelLexwareOffice\Dto\Common;

use DateTimeImmutable;
use Pirabyte\LaravelLexwareOffice\Dto\Dto;
use Pirabyte\LaravelLexwareOffice\Support\Assert;

final readonly class UpdateResult implements Dto
{
    public function __construct(
        public string $id,
        public string $resourceUri,
        public DateTimeImmutable $createdDate,
        public DateTimeImmutable $updatedDate,
        public int $version,
    ) {
        Assert::nonEmptyString($this->id, 'UpdateResult.id must be non-empty');
        Assert::nonEmptyString($this->resourceUri, 'UpdateResult.resourceUri must be non-empty');
        Assert::intRange($this->version, 0, PHP_INT_MAX, 'UpdateResult.version must be >= 0');
    }
}


