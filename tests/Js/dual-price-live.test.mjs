// Der Zweitpreis folgt einem in der Seite berechneten Preis (Meterpreis aus RcDynamicPrice). Anlass: Theme BRIEF167,
// die Nettozeile blieb nach der Längeneingabe beim Meterpreis stehen.
// Ohne Abhängigkeiten, nur mit der Standardbibliothek von Node (node:test).

import { describe, test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const stripped = readFileSync(
    join(__dirname, '..', '..', 'src', 'Resources', 'app', 'storefront', 'src', 'dual-price-live', 'dual-price-live.plugin.js'),
    'utf8',
).replace(/^import [^\n]*\n/gm, '').replace(/^export default /m, '');

const DualPriceLivePlugin = new Function(`
    class Plugin { init() {} destroy() {} }
    ${stripped}
    return DualPriceLivePlugin;
`)();

let listeners;

beforeEach(() => {
    listeners = {};
    globalThis.document = {
        documentElement: { lang: 'de-DE' },
        addEventListener: (type, fn) => { listeners[type] = fn; },
        removeEventListener: (type) => { delete listeners[type]; },
    };
});

function plugin(dataset = { taxRate: '19', taxState: 'gross', currency: 'EUR' }) {
    const amount = { textContent: '57,18 €' };
    const listPrice = { hidden: false };
    const instance = Object.create(DualPriceLivePlugin.prototype);
    instance.options = DualPriceLivePlugin.options;
    instance.el = {
        dataset,
        querySelector: () => amount,
        querySelectorAll: () => [listPrice],
    };
    instance.init();

    return { instance, amount, listPrice };
}

describe('Umrechnung wie DualPriceCalculator', () => {
    test('Brutto zu Netto und Netto zu Brutto, auf zwei Stellen', () => {
        assert.strictEqual(DualPriceLivePlugin.convert(102.06, 19, 'gross'), 85.76);
        assert.strictEqual(DualPriceLivePlugin.convert(85.76, 19, 'net'), 102.05);
        assert.strictEqual(DualPriceLivePlugin.convert(68.04, 19, 'gross'), 57.18);
    });

    test('ohne Preis, ohne Steuersatz oder bei unbekanntem State kein Zweitpreis', () => {
        assert.strictEqual(DualPriceLivePlugin.convert(null, 19, 'gross'), null);
        assert.strictEqual(DualPriceLivePlugin.convert(10, 0, 'gross'), null);
        assert.strictEqual(DualPriceLivePlugin.convert(10, Number.NaN, 'gross'), null);
        assert.strictEqual(DualPriceLivePlugin.convert(10, 19, 'tax-free'), null);
    });
});

describe('rcDynamicPriceChanged', () => {
    test('der berechnete Preis setzt den Zweitpreis, der Listenpreis tritt zurück', () => {
        const { amount, listPrice } = plugin();

        listeners.rcDynamicPriceChanged({ detail: { unitPrice: 102.06 } });

        assert.match(amount.textContent, /^85,76\s€$/);
        assert.strictEqual(listPrice.hidden, true);
    });

    test('zurückgesetzt gilt wieder der Wert vom Server', () => {
        const { amount, listPrice } = plugin();
        listeners.rcDynamicPriceChanged({ detail: { unitPrice: 102.06 } });

        listeners.rcDynamicPriceChanged({ detail: { unitPrice: null } });

        assert.strictEqual(amount.textContent, '57,18 €');
        assert.strictEqual(listPrice.hidden, false);
    });

    test('ohne Betragselement hört das Skript auf nichts', () => {
        const instance = Object.create(DualPriceLivePlugin.prototype);
        instance.options = DualPriceLivePlugin.options;
        instance.el = { dataset: {}, querySelector: () => null };
        instance.init();

        assert.deepStrictEqual(Object.keys(listeners), []);
    });
});
