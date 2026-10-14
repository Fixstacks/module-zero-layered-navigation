<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero_LayeredNavigation: several values of one filter at once, counts that
 * stay meaningful while filtering, a price range by the customer's own
 * numbers. See README.md.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Zero_LayeredNavigation', __DIR__);
