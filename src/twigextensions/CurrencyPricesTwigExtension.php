<?php
/**
 * Currency Prices plugin for Craft CMS 3.x
 *
 * Adds payment currency prices to products
 *
 * @link      https://webdna.co.uk/
 * @copyright Copyright (c) 2018 webdna
 */

namespace webdna\commerce\currencyprices\twigextensions;

use webdna\commerce\currencyprices\CurrencyPrices;
use craft\commerce\errors\CurrencyException;
use craft\commerce\Plugin as Commerce;
use craft\helpers\Localization;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author    webdna
 * @package   CurrencyPrices
 * @since     1.0.0
 */
class CurrencyPricesTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'CurrencyPrices';
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return [
			new TwigFilter('currencyPrice', [$this, 'currencyPrice']),
			new TwigFilter('currencySalePrice', [$this, 'currencySalePrice']),
			new TwigFilter('currencyAddonDiscountPrice', [$this, 'currencyAddonDiscountPrice']),
			new TwigFilter('currencyAddonDiscountPrices', [$this, 'currencyAddonDiscountPrices']),
			new TwigFilter('localizationNormalizeNumber', [$this, 'localizationNormalizeNumber']),
        ];
    }

    /**
     * Formats and optionally converts a currency amount into the supplied valid payment currency as per the rate setup in payment currencies.
     *
     * @param      $amount
     * @param      $currency
     * @param bool $format
     * @param bool $stripZeros
     * @return string
     */
    public function currencyPrice($purchasable, $currency, $format = true, $stripZeros = false): string
    {
		$this->_validatePaymentCurrency($currency);

		$prices = CurrencyPrices::$plugin->service->getPricesByPurchasableId($purchasable->id);
		$amount = '';

		if ($prices) {
			$amount = $prices[$currency];
		}

        // return input if no currency passed, and both convert and format are false.
        if (!$format) {
            return $amount;
        }

        if ($format) {
            $amount = Craft::$app->getFormatter()->asCurrency($amount, $currency, [], [], $stripZeros);
        }

        return $amount;
	}

	public function currencySalePrice($purchasable, $currency, $format = true, $stripZeros = false): string
    {
		$this->_validatePaymentCurrency($currency);

		$salePrice = CurrencyPrices::$plugin->service->getSalePrice($purchasable, $currency);

		if (!$format) {
            return $salePrice;
        }

        if ($format) {
            $salePrice = Craft::$app->getFormatter()->asCurrency($salePrice, $currency, [], [], $stripZeros);
        }

        return $salePrice;

	}

	public function currencyAddonDiscountPrice($discountId, $currency, $format = true, $stripZeros = false): string
	{
		$this->_validatePaymentCurrency($currency);

		$discount = CurrencyPrices::$plugin->addons->getPricesByAddonIdAndCurrency($discountId, $currency);

		if (!$discount) {
			return null;
		}

        // return input if no currency passed, and both convert and format are false.
        if (!$format) {
            return $discount['perItemDiscount'] * -1;
        }

        if ($format) {
            $discount = Craft::$app->getFormatter()->asCurrency($discount['perItemDiscount'] * -1, $currency, [], [], $stripZeros);
        }

        return $discount;
	}

	public function currencyAddonDiscountPrices($discountId): array
	{
		$discounts = CurrencyPrices::$plugin->addons->getPricesByAddonId($discountId);

		$prices = [];
		foreach ($discounts as $discount)
		{
			$prices[$discount['paymentCurrencyIso']] = $discount['perItemDiscount'] * -1;
		}

		return $prices;
    }

    public function localizationNormalizeNumber($number): mixed
    {
        return Localization::normalizeNumber($number);
    }


	// Private methods
    // =========================================================================

    /**
     * @param $currency
     * @throws \Twig_Error
     */
    private function _validatePaymentCurrency($currency): void
    {
        try {
            $currency = Commerce::getInstance()->getPaymentCurrencies()->getPaymentCurrencyByIso($currency);
        } catch (CurrencyException $exception) {
            throw new \Twig_Error($exception->getMessage());
        }
    }
}
