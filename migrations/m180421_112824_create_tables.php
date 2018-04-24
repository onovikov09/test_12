<?php

use yii\db\Migration;

/**
 * Class m180421_112824_create_tables
 */
class m180421_112824_create_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('wallet', [
            'id' => $this->primaryKey(11)->unsigned(),
            'number' => $this->string(25)->unique(),
            'full_name' => $this->string(100),
            'amount' => $this->decimal(19,4)->notNull()->defaultValue(0),
            'currency_key' => $this->string(3),
            'country_id' => $this->integer(11)->unsigned(),
            'city_id' => $this->integer(11)->unsigned(),
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createTable('country', [
            'id' => $this->primaryKey(11)->unsigned(),
            'key' => $this->string(3)->notNull()->unique(),
            'title' => $this->string(100),
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createTable('city', [
            'id' => $this->primaryKey(11)->unsigned(),
            'title' => $this->string(100)->unique(),
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createTable('currency', [
            'id' => $this->primaryKey(11)->unsigned(),
            'key' => $this->string(3)->notNull()->unique(),
            'title' => $this->string(100),
            'rate' => $this->decimal(10,5)->notNull(),
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createTable('wallet_log', [
            'id' => $this->primaryKey(11)->unsigned(),
            'wallet_to' => $this->integer(11)->unsigned()->notNull(),
            'wallet_from' => $this->integer(11)->unsigned(),
            'currency_key' => $this->string(3)->notNull(),
            'currency_sum' => $this->decimal(19,4)->notNull(),
            'usd_sum' => $this->decimal(19,4)->notNull(),
            'dt' => $this->dateTime() . ' DEFAULT NOW()',
            'description' => $this->string(200)
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createIndex("wallet_log_dt", "wallet_log", "dt");
        $this->createIndex("wallet_log_wto", "wallet_log", "wallet_to");

        $this->addForeignKey(
            'wallet_fk0',
            'wallet',
            'currency_key',
            'currency',
            'key',
            'RESTRICT',
            'RESTRICT'
        );

        $this->addForeignKey(
            'wallet_fk1',
            'wallet',
            'country_id',
            'country',
            'id',
            'RESTRICT',
            'RESTRICT'
        );

        $this->addForeignKey(
            'wallet_fk2',
            'wallet',
            'city_id',
            'city',
            'id',
            'RESTRICT',
            'RESTRICT'
        );
    }

    public function safeDown()
    {
        $this->execute("SET foreign_key_checks = 0;");

        $this->dropForeignKey(
            'wallet_fk0',
            'wallet'
        );

        $this->dropForeignKey(
            'wallet_fk1',
            'wallet'
        );

        $this->dropForeignKey(
            'wallet_fk2',
            'wallet'
        );

        $this->dropIndex("wallet_log_dt", "wallet_log");
        $this->dropIndex("wallet_log_wto", "wallet_log");

        $this->dropTable('country');
        $this->dropTable('city');
        $this->dropTable('currency');
        $this->dropTable('wallet');
        $this->dropTable('wallet_log');

        $this->execute("SET foreign_key_checks = 1;");
    }
}
