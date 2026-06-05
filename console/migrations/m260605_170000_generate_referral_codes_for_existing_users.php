<?php

use yii\db\Migration;

class m260605_170000_generate_referral_codes_for_existing_users extends Migration
{
    public function safeUp()
    {
        $users = $this->db->createCommand('SELECT id FROM botuser WHERE referral_code IS NULL OR referral_code = ""')
            ->queryAll();

        foreach ($users as $user) {
            do {
                $code = strtoupper(substr(md5(uniqid()), 0, 8));
                $exists = $this->db->createCommand('SELECT COUNT(*) FROM botuser WHERE referral_code = :code')
                    ->bindValue(':code', $code)
                    ->queryScalar();
            } while ($exists > 0);

            $this->db->createCommand()->update('botuser', ['referral_code' => $code], ['id' => $user['id']])->execute();
        }

        echo "    > " . count($users) . " ta foydalanuvchiga referral kod berildi.\n";
    }

    public function safeDown()
    {
        echo "safeDown not supported for this migration.\n";
        return false;
    }
}
