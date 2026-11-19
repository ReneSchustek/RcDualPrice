import DualPriceLivePlugin from './dual-price-live/dual-price-live.plugin';

// Zweitpreis, der einem in der Seite berechneten Preis folgt (Meterpreis aus RcDynamicPrice).
window.PluginManager.register('RcDualPriceLive', DualPriceLivePlugin, '[data-rc-dual-price-live]');
