<?php

namespace App\ValueObject;

class PaymentData
{
    private string $name;
    private array $translations;
    private array $currencies;
    private array $supportedCurrencies;

    public function __construct(
        string $name = 'external',
        array $translations = [],
        array $currencies = [],
        array $supportedCurrencies = []
    ) {
        if (!is_array($translations)) {
            throw new \InvalidArgumentException('Translations must be an array.');
        }
        $this->name = $name;
        $this->translations = $translations;
        $this->currencies = $currencies;
        $this->supportedCurrencies = $supportedCurrencies;
    }

    public static function createForNewPayment(
        string $title,
        string $description = null,
        bool $active = true,
        string $locale = 'pl_PL',
        string $notify = null,
        string $notifyMail = null,
        array $currencies = [1],
        array $supportedCurrencies = ['PLN']
    ): self {
        $translations = [
            $locale => [
                'title' => $title,
                'active' => $active
            ]
        ];

        if ($description !== null) {
            $translations[$locale]['description'] = $description;
        }

        if ($notify !== null) {
            $translations[$locale]['notify'] = $notify;
        }

        if ($notifyMail !== null) {
            $translations[$locale]['notify_mail'] = $notifyMail;
        }

        return new self('external', $translations, $currencies, $supportedCurrencies);
    }

    public static function createForUpdate(
        array $updateData,
        string $locale = 'pl_PL'
    ): self {
        $name = $updateData['name'] ?? 'external';
        $translations = $updateData['translations'] ?? [];
        $currencies = $updateData['currencies'] ?? [];
        $supportedCurrencies = $updateData['supportedCurrencies'] ?? [];

        if (empty($translations) && isset($updateData['title'])) {
            $translations[$locale]['title'] = $updateData['title'];
        }

        if (isset($updateData['active'])) {
            $translations[$locale]['active'] = (bool) $updateData['active'];
        }

        if (isset($updateData['description'])) {
            $translations[$locale]['description'] = $updateData['description'];
        }

        if (isset($updateData['notify'])) {
            $translations[$locale]['notify'] = $updateData['notify'];
        }

        if (isset($updateData['notify_mail'])) {
            $translations[$locale]['notify_mail'] = $updateData['notify_mail'];
        }

        return new self($name, $translations, $currencies, $supportedCurrencies);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getTitle(string $locale = null): ?string
    {
        if ($locale === null) {
            foreach ($this->translations as $localeData) {
                if (isset($localeData['title'])) {
                    return $localeData['title'];
                }
            }
            return null;
        }

        return $this->translations[$locale]['title'] ?? null;
    }

    public function getDescription(string $locale = null): ?string
    {
        if ($locale === null) {
            foreach ($this->translations as $localeData) {
                if (isset($localeData['description'])) {
                    return $localeData['description'];
                }
            }
            return null;
        }

        return $this->translations[$locale]['description'] ?? null;
    }

    public function isActive(string $locale = null): ?bool
    {
        if ($locale === null) {
            foreach ($this->translations as $localeData) {
                if (isset($localeData['active'])) {
                    return $localeData['active'];
                }
            }
            return null;
        }

        return $this->translations[$locale]['active'] ?? null;
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function getLocale(): string
    {
        $locale = array_key_first($this->translations);
        if ($locale === null) {
            throw new \RuntimeException('No locale set in PaymentData translations.');
        }
        return $locale;
    }

    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'translations' => $this->translations,
            'currencies' => $this->currencies,
            'supportedCurrencies' => $this->supportedCurrencies
        ];

        return $result;
    }
}
