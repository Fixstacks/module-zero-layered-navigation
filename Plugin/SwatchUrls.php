<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\LayeredNavigation\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Swatches\Block\LayeredNavigation\RenderLayered;
use Zero\LayeredNavigation\Model\Values;

/**
 * Swatch filters (color, size…) link like the others: a swatch adds its
 * value to the chosen ones, or takes it out.
 */
class SwatchUrls
{
    public function __construct(private readonly RequestInterface $request)
    {
    }

    public function aroundBuildUrl(RenderLayered $subject, callable $proceed, $attributeCode, $optionId)
    {
        $chosen = Values::parse($this->request->getParam((string) $attributeCode));

        return $proceed($attributeCode, Values::toggle($chosen, (string) $optionId));
    }
}
