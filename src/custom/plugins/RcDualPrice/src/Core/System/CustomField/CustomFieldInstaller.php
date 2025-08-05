<?php declare(strict_types=1);

namespace RcDualPrice\Core\System\CustomField;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class CustomFieldInstaller
{
    final public const SET_NAME = 'rc_dual_price_category_fields';
    final public const FIELD_NAME = 'rc_dual_price_active';

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository
    ) {}

    public function install(): void
    {
        error_log('RcDualPrice: CustomFieldInstaller::install() wird ausgeführt!');

        $context = Context::createDefaultContext();

        try {
            $this->customFieldSetRepository->upsert([
                [
                    'name' => self::SET_NAME,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Dual Price Settings',
                            'de-DE' => 'Zweitpreis Einstellungen',
                        ],
                    ],
                    'customFields' => [
                        [
                            'name' => self::FIELD_NAME,
                            'type' => 'bool',
                            'config' => [
                                'label' => [
                                    'en-GB' => 'Activate dual price display for this category',
                                    'de-DE' => 'Zweitpreis-Anzeige für diese Kategorie aktivieren',
                                ],
                                'componentName' => 'sw-field',
                                'customFieldType' => 'checkbox',
                                'type' => 'checkbox',
                            ],
                        ],
                    ],
                    'relations' => [
                        ['entityName' => 'category']
                    ],
                ]
            ], $context);

            error_log('RcDualPrice: Custom Field Set erfolgreich erstellt!');
        } catch (\Exception $e) {
            error_log('RcDualPrice: Fehler beim Erstellen des Custom Field Sets: ' . $e->getMessage());
        }
    }

    public function uninstall(): void
    {
        $context = Context::createDefaultContext();

        try {
            $this->customFieldSetRepository->delete([
                ['name' => self::SET_NAME]
            ], $context);
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }
    }
}