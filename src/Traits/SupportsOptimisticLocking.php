<?php

namespace Pirabyte\LaravelLexwareOffice\Traits;

trait SupportsOptimisticLocking
{
    /**
     * Get the version field name for this model
     */
    public function getVersionField(): string
    {
        return 'version';
    }

    /**
     * Get the lock version field name for this model
     */
    public function getLockVersionField(): string
    {
        return 'lockVersion';
    }

    /**
     * Get the current version/lockVersion value
     */
    public function getCurrentVersion(): ?int
    {
        // Check if model uses 'version' field (like Contact)
        if (method_exists($this, 'getVersion')) {
            return $this->getVersion();
        }

        // Check if model uses 'lockVersion' field (like FinancialAccount)
        if (method_exists($this, 'getLockVersion')) {
            return $this->getLockVersion();
        }

        return null;
    }

    /**
     * Set the current version/lockVersion value
     */
    public function setCurrentVersion(?int $version): self
    {
        // Check if model uses 'version' field (like Contact)
        if (method_exists($this, 'setVersion')) {
            $this->setVersion($version ?? 0);
            return $this;
        }

        // For models that only have lockVersion as read-only (like FinancialAccount),
        // we cannot set it directly, so we do nothing
        return $this;
    }

    /**
     * Check if this model supports optimistic locking
     */
    public function supportsOptimisticLocking(): bool
    {
        return method_exists($this, 'getVersion') || method_exists($this, 'getLockVersion');
    }

    /**
     * Increment the version for optimistic locking
     */
    public function incrementVersion(): self
    {
        $currentVersion = $this->getCurrentVersion();
        if ($currentVersion !== null && method_exists($this, 'setVersion')) {
            $this->setVersion($currentVersion + 1);
        }

        return $this;
    }

    /**
     * Prepare the model data for update with version checking
     */
    public function toArrayForUpdate(): array
    {
        $data = $this->jsonSerialize();

        // Ensure version is included for optimistic locking
        $version = $this->getCurrentVersion();
        if ($version !== null) {
            if (method_exists($this, 'getVersion')) {
                $data['version'] = $version;
            } elseif (method_exists($this, 'getLockVersion')) {
                $data['lockVersion'] = $version;
            }
        }

        return $data;
    }
}