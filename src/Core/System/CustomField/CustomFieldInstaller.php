<?php declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Core\System\CustomField;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

final class CustomFieldInstaller
{
    final public const SET_NAME = 'rc_dual_price_category_fields';
    final public const FIELD_NAME = 'rc_dual_price_active';

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository
    ) {}

    public function install(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::SET_NAME));
        $existing = $this->customFieldSetRepository->search($criteria, $context)->first();

        if ($existing !== null) {
            return;
        }

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
    }

    public function uninstall(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::SET_NAME));
        $set = $this->customFieldSetRepository->search($criteria, $context)->first();

        if ($set) {
            $this->customFieldSetRepository->delete([['id' => $set->getId()]], $context);
        }
    }
}
