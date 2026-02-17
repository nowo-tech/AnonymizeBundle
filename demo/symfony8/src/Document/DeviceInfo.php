<?php

declare(strict_types=1);

namespace App\Document;

use DateTimeInterface;

/**
 * DeviceInfo document for MongoDB.
 *
 * Demonstrates technical fakers: ip_address, mac_address, uuid, hash, coordinate, color.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class DeviceInfo
{
    private ?string $id                  = null;
    private ?string $deviceId            = null; // UUID
    private ?string $ipAddress           = null;
    private ?string $macAddress          = null;
    private ?string $deviceHash          = null;
    private ?string $location            = null; // Coordinates
    private ?string $themeColor          = null;
    private ?string $deviceName          = null;
    private ?string $osVersion           = null;
    private ?string $browserVersion      = null;
    private ?bool $isActive              = null;
    private ?DateTimeInterface $lastSeen = null;
    private bool $anonymized             = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    public function setDeviceId(?string $deviceId): static
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getMacAddress(): ?string
    {
        return $this->macAddress;
    }

    public function setMacAddress(?string $macAddress): static
    {
        $this->macAddress = $macAddress;

        return $this;
    }

    public function getDeviceHash(): ?string
    {
        return $this->deviceHash;
    }

    public function setDeviceHash(?string $deviceHash): static
    {
        $this->deviceHash = $deviceHash;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getThemeColor(): ?string
    {
        return $this->themeColor;
    }

    public function setThemeColor(?string $themeColor): static
    {
        $this->themeColor = $themeColor;

        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): static
    {
        $this->deviceName = $deviceName;

        return $this;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    public function setOsVersion(?string $osVersion): static
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    public function setBrowserVersion(?string $browserVersion): static
    {
        $this->browserVersion = $browserVersion;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLastSeen(): ?DateTimeInterface
    {
        return $this->lastSeen;
    }

    public function setLastSeen(?DateTimeInterface $lastSeen): static
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized;
    }

    public function setAnonymized(bool $anonymized): static
    {
        $this->anonymized = $anonymized;

        return $this;
    }
}
