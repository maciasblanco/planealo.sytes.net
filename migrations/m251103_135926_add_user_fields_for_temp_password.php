<?php

use yii\db\Migration;

class m251103_135926_add_user_fields_for_temp_password extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251103_135926_add_user_fields_for_temp_password cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251103_135926_add_user_fields_for_temp_password cannot be reverted.\n";

        return false;
    }
    */
}
