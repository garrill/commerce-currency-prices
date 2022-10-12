<?php
/**
 * Currency Prices plugin for Craft CMS 3.x
 *
 * Adds payment currency prices to products
 *
 * @link      https://webdna.co.uk/
 * @copyright Copyright (c) 2018 webdna
 */

namespace webdna\commerce\currencyprices\controllers;

use webdna\commerce\currencyprices\CurrencyPrices;

use Craft;
use craft\web\Controller;
use craft\commerce\models\PaymentCurrency;
use craft\commerce\Plugin as Commerce;
use craft\commerce\base\Purchasable;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Product;
use craft\commerce\models\Discount;
use craft\elements\Category;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\i18n\Locale;
use yii\web\HttpException;
use yii\web\Response;

/**
 * @author    webdna
 * @package   CurrencyPrices
 * @since     1.0.0
 */
class TicketsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected array|bool|int $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    public function actionGetInputs(): Response
    {
        $this->requireAcceptsJson();
        $id = Craft::$app->getRequest()->getParam('id');
        $name = Craft::$app->getRequest()->getRequiredParam('name');

        $variables = [
            "name" => $name,
            "id" => $name,
            "values" => $this->_getValues($id, str_replace('CP','',$name)),
            "errors" => [],
            "label" => Craft::$app->getRequest()->getParam('label'),
            "instructions" => Craft::$app->getRequest()->getParam('instructions'),
        ];

        return $this->asJson([
            'html' => $this->getView()->renderTemplate('commerce-currency-prices/field', $variables)
        ]);
    }

    /**
     * @throws HttpException
     */
    public function actionSave(): void
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $fields = CurrencyPrices::$plugin->tickets->getPrices(false);
        $request->setBodyParams(array_merge($request->getBodyParams(), $fields));

        Craft::$app->runAction('events/events/save');

    }

    private function _getValues($id, $prop): array
    {
        $values = [];
        $prices = [];
        if ($id) {
            $prices = CurrencyPrices::$plugin->tickets->getPricesByTicketId($id);
        }

        foreach (Commerce::getInstance()->getPaymentCurrencies()->getAllPaymentCurrencies() as $currency)
        {
            $val = null;
            foreach ($prices as $price)
            {
                if ($currency->iso == $price['paymentCurrencyIso']) {
                    $val = $price;
                }
            }
            //Craft::dd($prop);
            if ($val) {
                $price = $val[$prop] != 0 ? $val[$prop] * -1 : 0;
                $values[$currency->iso] = ['iso'=>$currency->iso, 'price'=>$price];
            } else {
                $values[$currency->iso] = ['iso'=>$currency->iso, 'price'=>0];
            }
        }

        return $values;
    }

}
