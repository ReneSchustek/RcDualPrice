<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Core\System\CustomField;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;

final class CustomFieldInstaller
{
    final public const SET_NAME = 'rc_dual_price_category_fields';
    final public const FIELD_NAME = 'rc_dual_price_active';
    private const LOG_CONTEXT = 'ruhrcoder_dual_price.custom_field_installer';

    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     */
    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function install(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::SET_NAME));
        $existing = $this->customFieldSetRepository->search($criteria, $context)->first();

        if ($existing !== null) {
            $this->logger->info('RcDualPrice: CustomFieldSet bereits vorhanden, Install ist No-op.', [
                'context' => self::LOG_CONTEXT,
                'setName' => self::SET_NAME,
            ]);

            return;
        }

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
                        ['entityName' => 'category'],
                    ],
                ],
            ], $context);

            $this->logger->info('RcDualPrice: CustomFieldSet angelegt.', [
                'context' => self::LOG_CONTEXT,
                'setName' => self::SET_NAME,
                'fieldName' => self::FIELD_NAME,
            ]);
        } catch (\Throwable $exception) {
            // Lifecycle-Fehler werden hochgereicht (Shopware bricht Install ab), aber wir loggen vorher
            // strukturiert, damit Ops-Team Root-Cause sehen kann.
            $this->logger->error('RcDualPrice: CustomFieldSet-Install fehlgeschlagen.', [
                'context' => self::LOG_CONTEXT,
                'setName' => self::SET_NAME,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            // Original als previous erhalten (Stacktrace-Chain), Shopware bricht den Install weiterhin ab.
            throw new \RuntimeException('RcDualPrice: CustomFieldSet-Install fehlgeschlagen.', 0, $exception);
        }
    }

    public function uninstall(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::SET_NAME));
        $set = $this->customFieldSetRepository->search($criteria, $context)->first();

        if ($set === null) {
            $this->logger->info('RcDualPrice: CustomFieldSet bereits abwesend, Uninstall ist No-op.', [
                'context' => self::LOG_CONTEXT,
                'setName' => self::SET_NAME,
            ]);

            return;
        }

        try {
            $this->customFieldSetRepository->delete([['id' => $set->getId()]], $context);

            $this->logger->info('RcDualPrice: CustomFieldSet entfernt.', [
                'context' => self::LOG_CONTEXT,
                'setName' => self::SET_NAME,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('RcDualPrice: CustomFieldSet-Uninstall fehlgeschlagen.', [
                'context' => self::LOG_CONTEXT,
                'setName' => self::SET_NAME,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            // Original als previous erhalten (Stacktrace-Chain), Shopware bricht den Uninstall weiterhin ab.
            throw new \RuntimeException('RcDualPrice: CustomFieldSet-Uninstall fehlgeschlagen.', 0, $exception);
        }
    }
}
