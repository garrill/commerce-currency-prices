<?php
/**
 * Currency Prices plugin for Craft CMS 3.x
 *
 * Adds payment currency prices to products
 *
 * @link      https://webdna.co.uk/
 * @copyright Copyright (c) 2018 webdna
 */

namespace webdna\commerce\currencyprices\migrations;

use webdna\commerce\currencyprices\CurrencyPrices;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

use craft\commerce\Plugin as Commerce;

/**
 * @author    webdna
 * @package   CurrencyPrices
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables(): bool
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema(
            '{{%commerce_currencyprices}}'
        );
        if ($tableSchema === null) {
            $tablesCreated = true;

            $paymentCurrencies = [];
            foreach (
                Commerce::getInstance()
                    ->getPaymentCurrencies()
                    ->getAllPaymentCurrencies()
                as $currency
            ) {
                $paymentCurrencies[$currency->iso] = $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0)
                    ->unsigned();
            }

            $this->createTable(
                '{{%commerce_currencyprices}}',
                array_merge(
                    [
                        'id' => $this->primaryKey(),
                        'purchasableId' => $this->integer()->notNull(),
                        'dateCreated' => $this->dateTime()->notNull(),
                        'dateUpdated' => $this->dateTime()->notNull(),
                        'uid' => $this->uid(),
                        'siteId' => $this->integer()->notNull(),
                    ],
                    $paymentCurrencies
                )
            );

            $this->createTable(
                '{{%commerce_shippingrule_categories_currencyprices}}',
                [
                    'id' => $this->primaryKey(),
                    'shippingRuleId' => $this->integer(),
                    'shippingCategoryId' => $this->integer(),
                    'paymentCurrencyIso' => $this->string(3)->notNull(),
                    'perItemRate' => $this->decimal(14, 4),
                    'weightRate' => $this->decimal(14, 4),
                    'percentageRate' => $this->decimal(14, 4),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );

            $this->createTable('{{%commerce_shippingrules_currencyprices}}', [
                'id' => $this->primaryKey(),
                'shippingRuleId' => $this->integer(),
                'paymentCurrencyIso' => $this->string(3)->notNull(),
                'minTotal' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'maxTotal' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'minWeight' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'maxWeight' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'baseRate' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'perItemRate' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'weightRate' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'percentageRate' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'minRate' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'maxRate' => $this->decimal(14, 4)
                    ->notNull()
                    ->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createTable('{{%commerce_discounts_currencyprices}}', [
                'id' => $this->primaryKey(),
                'discountId' => $this->integer(),
                'paymentCurrencyIso' => $this->string(3)->notNull(),
                'purchaseTotal' => $this->decimal(14, 4),
                'baseDiscount' => $this->decimal(14, 4),
                'perItemDiscount' => $this->decimal(14, 4),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createTable('{{%addons_discounts_currencyprices}}', [
                'id' => $this->primaryKey(),
                'discountId' => $this->integer(),
                'paymentCurrencyIso' => $this->string(3)->notNull(),
                'perItemDiscount' => $this->decimal(14, 4),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes(): void
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%commerce_currencyprices}}',
                'purchasableId',
                true
            ),
            '{{%commerce_currencyprices}}',
            'purchasableId',
            true
        );
        $this->createIndex(
            null,
            '{{%commerce_shippingrule_categories_currencyprices}}',
            'shippingRuleId',
            false
        );
        $this->createIndex(
            null,
            '{{%commerce_shippingrule_categories_currencyprices}}',
            'shippingCategoryId',
            false
        );
        $this->createIndex(
            null,
            '{{%commerce_shippingrule_categories_currencyprices}}',
            'paymentCurrencyIso',
            false
        );
        $this->createIndex(
            null,
            '{{%commerce_shippingrules_currencyprices}}',
            'shippingRuleId',
            false
        );
        $this->createIndex(
            null,
            '{{%commerce_shippingrules_currencyprices}}',
            'paymentCurrencyIso',
            false
        );
        $this->createIndex(
            null,
            '{{%commerce_discounts_currencyprices}}',
            'discountId',
            false
        );
        $this->createIndex(
            null,
            '{{%commerce_discounts_currencyprices}}',
            'paymentCurrencyIso',
            false
        );
        $this->createIndex(
            null,
            '{{%addons_discounts_currencyprices}}',
            'discountId',
            false
        );
        $this->createIndex(
            null,
            '{{%addons_discounts_currencyprices}}',
            'paymentCurrencyIso',
            false
        );

        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * @return void
     */
    protected function addForeignKeys(): void
    {
        //$this->addForeignKey(null, '{{%commerce_currencyprices}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE');

        $this->addForeignKey(
            null,
            '{{%commerce_shippingrule_categories_currencyprices}}',
            ['shippingCategoryId'],
            '{{%commerce_shippingcategories}}',
            ['id'],
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%commerce_shippingrule_categories_currencyprices}}',
            ['shippingRuleId'],
            '{{%commerce_shippingrules}}',
            ['id'],
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%commerce_shippingrule_categories_currencyprices}}',
            ['paymentCurrencyIso'],
            '{{%commerce_paymentcurrencies}}',
            ['iso'],
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%commerce_shippingrules_currencyprices}}',
            ['shippingRuleId'],
            '{{%commerce_shippingrules}}',
            ['id'],
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%commerce_shippingrules_currencyprices}}',
            ['paymentCurrencyIso'],
            '{{%commerce_paymentcurrencies}}',
            ['iso'],
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%commerce_discounts_currencyprices}}',
            ['discountId'],
            '{{%commerce_discounts}}',
            ['id'],
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%commerce_discounts_currencyprices}}',
            ['paymentCurrencyIso'],
            '{{%commerce_paymentcurrencies}}',
            ['iso'],
            'CASCADE'
        );
        //$this->addForeignKey(null, '{{%addons_discounts_currencyprices}}', ['discountId'], '{{%addons_discounts}}', ['id'], 'CASCADE');
        //$this->addForeignKey(null, '{{%addons_discounts_currencyprices}}', ['paymentCurrencyIso'], '{{%commerce_paymentcurrencies}}', ['iso'], 'CASCADE');
    }

    /**
     * @return void
     */
    protected function insertDefaultData(): void
    {
    }

    /**
     * @return void
     */
    protected function removeTables(): void
    {
        $this->dropTableIfExists('{{%commerce_currencyprices}}');
        $this->dropTableIfExists(
            '{{%commerce_shippingrule_categories_currencyprices}}'
        );
        $this->dropTableIfExists('{{%commerce_shippingrules_currencyprices}}');
        $this->dropTableIfExists('{{%commerce_discounts_currencyprices}}');
        $this->dropTableIfExists('{{%addons_discounts_currencyprices}}');
    }
}
