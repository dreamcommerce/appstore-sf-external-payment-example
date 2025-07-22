<?php

namespace App\ValueObject;

class ChannelData
{
    private int $channelId;
    private string $applicationChannelId;
    private ?string $type;
    private array $translations;

    public function __construct(
        int $channelId = 0,
        string $applicationChannelId = '',
        ?string $type = null,
        array $translations = []
    ) {
        if (!is_array($translations)) {
            throw new \InvalidArgumentException('Translations must be an array.');
        }

        $this->channelId = $channelId;
        $this->applicationChannelId = $applicationChannelId;
        $this->type = $type;
        $this->translations = $translations;
    }

    public function getChannelId(): int
    {
        return $this->channelId;
    }

    public function getApplicationChannelId(): string
    {
        return $this->applicationChannelId;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?array
    {
        return $this->translations[$locale] ?? null;
    }

    public function hasTranslationForLocale(string $locale): bool
    {
        return isset($this->translations[$locale]);
    }

    public function toArray(): array
    {
        return [
            'channel_id' => $this->channelId,
            'application_channel_id' => $this->applicationChannelId,
            'type' => $this->type,
            'translations' => $this->translations
        ];
    }

    public function toApiArray(): array
    {
        return [
            'application_channel_id' => $this->applicationChannelId,
            'type' => $this->type,
            'translations' => $this->translations
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['channel_id'] ?? 0),
            $data['application_channel_id'] ?? '',
            !empty($data['type']) ? $data['type'] : null,
            $data['translations'] ?? []
        );
    }

    public static function createTranslation(
        string $name,
        string $description = '',
        string $additionalInfoLabel = ''
    ): array {
        return [
            'name'                  => $name,
            'description'           => $description,
            'additional_info_label' => $additionalInfoLabel
        ];
    }
}
