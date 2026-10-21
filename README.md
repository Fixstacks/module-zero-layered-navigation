# Zero_LayeredNavigation

Category and search page filters, improved (the Zero theme's filter
templates render them):

- **Several values of one filter at once**: Red *or* Blue
  (`?color=49,50`). A chosen option is a ticked checkbox (a chosen swatch is
  marked); its link takes it out, another option's adds it. Each chosen value
  is a chip of its own under "Now Shopping by", its × removing only it.
- **Counts that stay meaningful**: a filter with values chosen stays listed
  and open, its options counted as if it weren't applied, every other filter
  still is (one more search request per such filter, built the way the
  page's own is: `Model\FacetCounts`). So "Blue (12)" while Red is chosen
  says what adding Blue brings.
- **Price by the customer's own numbers**: a two-handle slider over the
  page's prices (the cheapest and dearest with the other filters applied,
  from the price index: `Model\PriceBounds`) and From/To fields
  (`price_from`, `price_to`, in the store view's currency, converted to the
  base one). Letting a handle go applies it. The slider is
  `view/frontend/web/js/price-slider.js`; without it the fields work alone.
  Magento's own `price=100-500` links still work.
- **Long lists**: the first 8 options, the rest under "Show more (N)" (open
  when one of them is chosen).
- Swatch filters refresh the page part with HTMX like the others.

How: `categoryFilterList` and `searchFilterList` (etc/di.xml) get Zero's
attribute and price filters; a plugin makes swatch links toggle;
`ViewModel\Filters` (layout argument `zero_filters`) tells the templates
what's chosen. Without the module the theme's templates behave as stock.

## Install

Requires Magento Open Source 2.4.7–2.4.9 and PHP 8.2–8.4 with the GD extension built with WebP (`php -r 'var_dump(function_exists("imagewebp"));'` says `true`; Magento's own PHP requirements cover the rest). Without WebP in GD, Zero skips its WebP image copies and serves the originals.

```bash
composer require zerofront/module-zero-layered-navigation
bin/magento setup:upgrade
bin/magento setup:di:compile           # production mode
bin/magento setup:static-content:deploy # production mode
bin/magento cache:flush
```

Part of [`zerofront/zero`](https://zerofront.dev), which installs the Zero theme and every Zero module at once.

## Tests

`Test/Unit/Model/{Values,Price}Test.php`; `dev/tests/zero-e2e/tests/
layered-navigation.spec.ts` (two colors, two options of a checkbox filter,
the price range).

## License

OSL-3.0 or AFL-3.0, your choice: see `NOTICE.md`.

---

<a href="https://vilmate.com"><picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/zerofrontdev/zero/main/docs/images/vilmate-logo-dark.svg">
  <img src="https://raw.githubusercontent.com/zerofrontdev/zero/main/docs/images/vilmate-logo.svg" alt="Vilmate" height="34" align="right">
</picture></a>

Zero is made by [Fixstacks](https://fixstacks.com) in partnership with [Vilmate](https://vilmate.com), a software development company.
Documentation: https://zerofront.dev/docs. Demo store: https://demo.zerofront.dev.
