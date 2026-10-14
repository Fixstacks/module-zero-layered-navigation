<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\LayeredNavigation\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory as MagentoItemFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filter\StripTags;
use Magento\Store\Model\StoreManagerInterface;
use Zero\LayeredNavigation\Model\FacetCounts;
use Zero\LayeredNavigation\Model\Values;

/**
 * An attribute filter with several values at once ("Red or Blue":
 * ?color=49,50). The filter stays listed while values are chosen; its options
 * are counted as if it weren't applied (FacetCounts), the other filters
 * still are. Each option's link adds or takes out its value (Item, zero_url);
 * its item knows whether it is chosen (zero_chosen). One state item per chosen value, each
 * removing only its own (zero_remove_url).
 */
class Attribute extends \Magento\CatalogSearch\Model\Layer\Filter\Attribute
{
    /** @var string[] */
    private array $chosen = [];

    private bool $isFixed = false;

    public function __construct(
        MagentoItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        private readonly StripTags $stripTags,
        private readonly FacetCounts $facetCounts,
        private readonly \Magento\Framework\UrlInterface $url,
        private readonly ItemFactory $zeroItemFactory,
        array $data = []
    ) {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $stripTags, $data);
    }

    public function apply(RequestInterface $request)
    {
        $fixed = FacetCounts::fixed($request)[$this->_requestVar] ?? null;
        if ($fixed !== null) {
            // Applied by the page itself (a brand's page): not listed, no chip.
            $this->getLayer()->getProductCollection()->addFieldToFilter($this->_requestVar, $fixed);
            $this->isFixed = true;

            return $this;
        }
        $this->chosen = Values::parse($request->getParam($this->_requestVar));
        if (!$this->chosen) {
            return $this;
        }
        $attribute = $this->getAttributeModel();
        $values = $attribute->getBackendType() === 'int' ? array_map('intval', $this->chosen) : $this->chosen;
        $this->getLayer()->getProductCollection()->addFieldToFilter(
            $attribute->getAttributeCode(),
            count($values) === 1 ? $values[0] : $values
        );
        foreach ($this->chosen as $value) {
            $label = $this->getOptionText($value);
            $item = $this->_createItem(is_array($label) ? implode(', ', $label) : (string) $label, $value);
            $item->setData('zero_remove_url', $this->toggleUrl($value));
            $this->getLayer()->getState()->addFilter($item);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getChosenValues(): array
    {
        return $this->chosen;
    }

    protected function _getItemsData()
    {
        if ($this->isFixed) {
            return [];
        }
        if (!$this->chosen) {
            return parent::_getItemsData();
        }
        $attribute = $this->getAttributeModel();
        [$others, $price] = $this->otherFilters();
        $facets = $this->facetCounts->without($this->getLayer(), $attribute->getAttributeCode(), $others, $price);
        foreach ($attribute->getFrontend()->getSelectOptions() as $option) {
            $value = (string) ($option['value'] ?? '');
            if ($value === '') {
                continue;
            }
            $count = (int) ($facets[$value]['count'] ?? 0);
            if ($count === 0 && !in_array($value, $this->chosen, true)) {
                continue;
            }
            $this->itemDataBuilder->addItemData($this->stripTags->filter((string) $option['label']), $value, $count);
        }

        return $this->itemDataBuilder->build();
    }

    protected function _initItems()
    {
        $items = [];
        foreach ($this->_getItemsData() as $itemData) {
            $value = (string) $itemData['value'];
            $items[] = $this->zeroItemFactory->create()
                ->setFilter($this)
                ->setLabel($itemData['label'])
                ->setValue($value)
                ->setCount($itemData['count'])
                ->setData('zero_url', $this->toggleUrl($value))
                ->setData('zero_chosen', in_array($value, $this->chosen, true));
        }
        $this->_items = $items;

        return $this;
    }

    /**
     * The current page with this filter's $value added or taken out.
     */
    public function toggleUrl(string $value): string
    {
        return $this->url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => [
            $this->_requestVar => Values::toggle($this->chosen, $value),
            'p' => null,
        ]]);
    }

    /**
     * The other filters applied: attribute values by code, and the price range.
     *
     * @return array{0: array<string, string[]>, 1: array|null}
     */
    private function otherFilters(): array
    {
        $filters = [];
        $price = null;
        foreach ($this->getLayer()->getState()->getFilters() as $item) {
            $filter = $item->getFilter();
            if ($filter instanceof self && $filter !== $this) {
                $filters[$filter->getAttributeModel()->getAttributeCode()] = $filter->getChosenValues();
            } elseif ($filter instanceof Price) {
                $price = $filter->getChosenRange();
            }
        }

        return [$filters, $price];
    }
}
