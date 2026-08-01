<?php

declare(strict_types=1);

namespace Ruhrcoder\RcDualPrice\Service;

/**
 * Rechnet einen Einzelpreis in den jeweils anderen Steuer-State um — den "Zweitpreis".
 *
 * Zuvor lag diese Arithmetik un-getestet und un-gerundet inline im Twig
 * (rc-dual-price-label.html.twig). Als Service ist sie testbar und liefert einen deterministisch
 * gerundeten Wert (vermeidet die sub-Cent-Drift der frueheren Roh-Division/-Multiplikation).
 *
 * Grenzen bewusst eng: nur die echten States 'gross'/'net' erzeugen einen Zweitpreis. 'tax-free'
 * (und jeder unbekannte State) sowie ein Steuersatz von 0/null geben `null` zurueck — dann darf
 * nichts gerendert werden, sonst wuerde auf einen steuerfreien Preis ein Brutto-Wert fabriziert.
 */
final class DualPriceCalculator
{
    public const STATE_GROSS = 'gross';
    public const STATE_NET = 'net';

    /**
     * @return float|null der umgerechnete Zweitpreis (auf 2 Nachkommastellen gerundet) oder null,
     *                     wenn kein Zweitpreis darstellbar ist
     */
    public function convert(?float $unitPrice, ?float $taxRate, ?string $taxState): ?float
    {
        if ($unitPrice === null || $taxRate === null || $taxRate <= 0.0) {
            return null;
        }

        $factor = 1 + ($taxRate / 100);

        $converted = match ($taxState) {
            self::STATE_GROSS => $unitPrice / $factor, // Brutto -> Netto
            self::STATE_NET => $unitPrice * $factor,    // Netto -> Brutto
            default => null,
        };

        if ($converted === null) {
            return null;
        }

        return round($converted, 2);
    }
}
