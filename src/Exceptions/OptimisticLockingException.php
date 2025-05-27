<?php

namespace Pirabyte\LaravelLexwareOffice\Exceptions;

use Exception;

class OptimisticLockingException extends LexwareOfficeApiException
{
    /**
     * The current version from the server
     */
    protected ?int $currentVersion = null;

    /**
     * The attempted version that failed
     */
    protected ?int $attemptedVersion = null;

    /**
     * The entity ID that failed to update
     */
    protected ?string $entityId = null;

    /**
     * Create a new optimistic locking exception
     *
     * @param string $message The error message
     * @param string|null $entityId The ID of the entity that failed to update
     * @param int|null $attemptedVersion The version that was attempted to be updated
     * @param int|null $currentVersion The current version on the server
     * @param Exception|null $previous The previous exception
     */
    public function __construct(
        string $message = 'Optimistic locking conflict: The entity was modified by another process',
        ?string $entityId = null,
        ?int $attemptedVersion = null,
        ?int $currentVersion = null,
        ?Exception $previous = null
    ) {
        $this->entityId = $entityId;
        $this->attemptedVersion = $attemptedVersion;
        $this->currentVersion = $currentVersion;

        // Enhance the message with version information if available
        if ($attemptedVersion !== null && $currentVersion !== null) {
            $message .= " (attempted version: {$attemptedVersion}, current version: {$currentVersion})";
        }

        if ($entityId !== null) {
            $message .= " for entity: {$entityId}";
        }

        parent::__construct($message, self::STATUS_CONFLICT, $previous);
    }

    /**
     * Get the current version from the server
     */
    public function getCurrentVersion(): ?int
    {
        return $this->currentVersion;
    }

    /**
     * Get the attempted version that failed
     */
    public function getAttemptedVersion(): ?int
    {
        return $this->attemptedVersion;
    }

    /**
     * Get the entity ID that failed to update
     */
    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    /**
     * Check if this is an optimistic locking conflict
     */
    public function isOptimisticLockingConflict(): bool
    {
        return true;
    }

    /**
     * Get a user-friendly message explaining the conflict
     */
    public function getUserMessage(): string
    {
        return 'The data you are trying to update has been modified by another user. Please refresh and try again.';
    }

    /**
     * Get suggested retry action
     */
    public function getRetryAction(): string
    {
        return 'Fetch the latest version of the entity and retry the update operation.';
    }
}