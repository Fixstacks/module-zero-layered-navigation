/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero layered navigation: the price filter's two-handle slider
 * ([data-zero-price-slider], hidden until this script shows it). Moving a
 * handle writes its number into the form's From/To fields (an end of the
 * scale leaves its field empty: no limit); letting it go submits the form
 * (HTMX refreshes the products). Typing a number moves its handle. JavaScript
 * because a slider's live numbers can't be done without it; without it the
 * From/To fields work as they are. Filters are swapped in again after every
 * choice, so sliders are set up after each swap too.
 */
(function () {
    'use strict';

    function setUp(slider) {
        if (slider.dataset.ready) {
            return;
        }
        slider.dataset.ready = '1';
        slider.hidden = false;
        var form = slider.closest('form');
        var low = slider.querySelector('[data-low]');
        var high = slider.querySelector('[data-high]');
        var from = form.querySelector('input[name="price_from"]');
        var to = form.querySelector('input[name="price_to"]');
        var min = Number(low.min);
        var max = Number(low.max);

        function paint() {
            var span = max - min || 1;
            slider.style.setProperty('--low', ((Number(low.value) - min) / span * 100) + '%');
            slider.style.setProperty('--high', ((Number(high.value) - min) / span * 100) + '%');
        }

        function fromHandles(moved) {
            if (Number(low.value) > Number(high.value)) {
                if (moved === low) {
                    high.value = low.value;
                } else {
                    low.value = high.value;
                }
            }
            from.value = Number(low.value) <= min ? '' : low.value;
            to.value = Number(high.value) >= max ? '' : high.value;
            paint();
        }

        function fromFields() {
            low.value = from.value === '' ? min : Math.max(min, Math.min(max, Number(from.value)));
            high.value = to.value === '' ? max : Math.max(min, Math.min(max, Number(to.value)));
            paint();
        }

        [low, high].forEach(function (handle) {
            handle.addEventListener('input', function () {
                fromHandles(handle);
            });
            handle.addEventListener('change', function () {
                form.requestSubmit();
            });
        });
        [from, to].forEach(function (field) {
            field.addEventListener('input', fromFields);
        });
        paint();
    }

    function setUpAll() {
        document.querySelectorAll('[data-zero-price-slider]').forEach(setUp);
    }

    document.addEventListener('htmx:afterSettle', setUpAll);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setUpAll);
    } else {
        setUpAll();
    }
})();
