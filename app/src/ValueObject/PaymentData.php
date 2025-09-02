<?php

declare(strict_types=1);

namespace App\ValueObject;

use InvalidArgumentException;
use RuntimeException;

class PaymentData
{
    public const DEFAULT_NOTIFY_TEMPLATE = "<strong>Dziękujemy za złożenie zamówienia</strong><br aria-hidden=\"true\"><br aria-hidden=\"true\">{if confirmation}Za chwilę otrzymasz e-mail z prośbą o jego potwierdzenie. {/if}<br aria-hidden=\"true\"><br aria-hidden=\"true\">Numer Twojego zamówienia: <strong>{order_id}</strong> <br aria-hidden=\"true\">Całkowita wartość zakupów, wraz z kosztami wysyłki: <strong>{sum}</strong><br aria-hidden=\"true\"><br aria-hidden=\"true\"><strong>Aby opłacić zamówienie kliknij na poniższy przycisk:</strong><br aria-hidden=\"true\">{payment_form}<br aria-hidden=\"true\"><br aria-hidden=\"true\">O zmianie statusu będziemy Cię również informować pocztą elektroniczną.<br aria-hidden=\"true\"><br aria-hidden=\"true\">W razie jakichkolwiek pytań lub wątpliwości prosimy o kontakt <br aria-hidden=\"true\">telefoniczny: {shop_phone} lub e-mailowy {shop_email}<br aria-hidden=\"true\"><br aria-hidden=\"true\">Pozdrawiamy,<br aria-hidden=\"true\">Zespół Obsługi Sklepu {shop_name}<br aria-hidden=\"true\"><br aria-hidden=\"true\">";
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
            throw new InvalidArgumentException('Translations must be an array.');
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
        string $notify = self::DEFAULT_NOTIFY_TEMPLATE,
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
            throw new RuntimeException('No locale set in PaymentData translations.');
        }
        return $locale;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'translations' => $this->translations,
            'currencies' => $this->currencies,
            'supportedCurrencies' => $this->supportedCurrencies
        ];
    }
}
