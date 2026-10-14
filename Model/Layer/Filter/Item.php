<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\LayeredNavigation\Model\Layer\Filter;

/**
 * A filter option: its value stays the option id (as swatches and other code
 * expect), its link adds or takes it out of the chosen values (zero_url).
 */
class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{
    public function getUrl()
    {
        return (string) ($this->getData('zero_url') ?: parent::getUrl());
    }
}
