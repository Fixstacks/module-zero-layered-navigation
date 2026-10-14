<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\LayeredNavigation\Model;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Cache\StateInterface as CacheState;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Zero\LayeredNavigation\Model\Layer\Filter\Attribute;

/**
 * The price slider's ends: cheapest and dearest of the page's products with
 * every other filter applied, from the price index, in the store currency,
 * rounded outwards. Block-cached for an hour (cleaned by the category's
 * product saves): two queries, ~20-40 ms.
 */
class PriceBounds
{
    /** @var array{0: float, 1: float}|null|false */
    private array|null|false $bounds = false;

    private const CACHE_LIFETIME = 3600;

    public function __construct(
        private readonly FacetCounts $facetCounts,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly CacheInterface $cache,
        private readonly CacheState $cacheState,
        private readonly HttpContext $httpContext,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @return array{0: float, 1: float}|null Null when there is nothing to slide over
     */
    public function get(Layer $layer): ?array
    {
        if ($this->bounds !== false) {
            return $this->bounds;
        }
        $filters = [];
        foreach ($layer->getState()->getFilters() as $item) {
            $filter = $item->getFilter();
            if ($filter instanceof Attribute) {
                $filters[$filter->getAttributeModel()->getAttributeCode()] = $filter->getChosenValues();
            }
        }
        ksort($filters);
        $categoryId = (int) $layer->getCurrentCategory()->getId();
        $cacheable = $this->cacheState->isEnabled(BlockCache::TYPE_IDENTIFIER);
        $key = 'zero_price_bounds_' . hash('sha256', json_encode([
            (int) $layer->getCurrentStore()->getId(),
            $categoryId,
            (string) $this->request->getParam('q', ''),
            $filters,
            (string) $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP),
            (string) $this->priceCurrency->getCurrency()->getCode(),
        ]));
        if ($cacheable) {
            $cached = $this->cache->load($key);
            if (is_string($cached)) {
                $decoded = json_decode($cached, true);

                return $this->bounds = is_array($decoded) ? [(float) $decoded[0], (float) $decoded[1]] : null;
            }
        }
        $this->bounds = $this->compute($layer, $filters);
        if ($cacheable) {
            $this->cache->save(
                (string) json_encode($this->bounds),
                $key,
                ['cat_c_p_' . $categoryId, BlockCache::CACHE_TAG],
                self::CACHE_LIFETIME
            );
        }

        return $this->bounds;
    }

    /**
     * @param array<string, array<int, string>> $filters
     * @return array{0: float, 1: float}|null
     */
    private function compute(Layer $layer, array $filters): ?array
    {
        $ids = $this->facetCounts->collection($layer, $filters, null)->getAllIds();
        if (!$ids) {
            return null;
        }
        $prices = $this->productCollectionFactory->create()->addIdFilter($ids)->addPriceData();
        $min = floor((float) $this->priceCurrency->convert((float) $prices->getMinPrice()));
        $max = ceil((float) $this->priceCurrency->convert((float) $prices->getMaxPrice()));

        return $max > $min ? [$min, $max] : null;
    }
}
