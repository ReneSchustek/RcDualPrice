import Plugin from 'src/plugin-system/plugin.class';

/**
 * Hält den Zweitpreis der Produktseite beim Preis, wenn die Seite ihn selbst berechnet.
 *
 * Der Zweitpreis steht vom Server fest im HTML. RcDynamicPrice rechnet bei Meterpreis-Artikeln aus der Länge einen
 * neuen Preis und meldet ihn mit `rcDynamicPriceChanged` auf `document`; ohne dieses Skript blieb die Nettozeile beim
 * Meterpreis stehen, während darüber der berechnete Preis stand (Theme BRIEF167). Gerechnet wird wie
 * `DualPriceCalculator` auf dem Server: Brutto durch den Faktor oder Netto mal den Faktor, auf zwei Stellen gerundet.
 * Ist der berechnete Preis zurückgesetzt (`unitPrice: null`), gilt wieder der Wert vom Server.
 *
 * Erwartet am Element `data-tax-rate`, `data-tax-state` (`gross` oder `net`) und `data-currency`, darin den Betrag in
 * `[data-rc-dual-price-amount]`.
 */
export default class DualPriceLivePlugin extends Plugin {
    static options = {
        priceChangedEvent: 'rcDynamicPriceChanged',
    };

    init() {
        this._amount = this.el.querySelector('[data-rc-dual-price-amount]');
        if (!this._amount) {
            return;
        }

        this._serverText = this._amount.textContent;
        this._onPriceChanged = this._onPriceChanged.bind(this);
        document.addEventListener(this.options.priceChangedEvent, this._onPriceChanged);
    }

    destroy() {
        if (this._onPriceChanged) {
            document.removeEventListener(this.options.priceChangedEvent, this._onPriceChanged);
        }
        super.destroy();
    }

    _onPriceChanged(event) {
        const unitPrice = event && event.detail ? event.detail.unitPrice : null;
        const converted = DualPriceLivePlugin.convert(unitPrice, parseFloat(this.el.dataset.taxRate), this.el.dataset.taxState);

        this._amount.textContent = converted === null ? this._serverText : this._format(converted);
        // Der durchgestrichene Listenpreis gehört zum Preis vom Server, nicht zum berechneten.
        this.el.querySelectorAll('.rc-dual-price-list-price').forEach((element) => {
            element.hidden = converted !== null;
        });
    }

    _format(value) {
        return new Intl.NumberFormat(document.documentElement.lang || 'de-DE', {
            style: 'currency',
            currency: this.el.dataset.currency || 'EUR',
        }).format(value);
    }

    /**
     * Wie `DualPriceCalculator::convert()`: `null` ohne Preis, ohne Steuersatz oder bei unbekanntem Steuer-State.
     */
    static convert(unitPrice, taxRate, taxState) {
        if (unitPrice === null || unitPrice === undefined || !(taxRate > 0)) {
            return null;
        }

        const factor = 1 + (taxRate / 100);
        let converted = null;
        if (taxState === 'gross') {
            converted = unitPrice / factor;
        } else if (taxState === 'net') {
            converted = unitPrice * factor;
        }

        return converted === null ? null : Math.round((converted + Number.EPSILON) * 100) / 100;
    }
}
