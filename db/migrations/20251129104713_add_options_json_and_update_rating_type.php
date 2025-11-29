<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOptionsJsonAndUpdateRatingType extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        // Add JSON column
        $this->table('evaluation_questions')
            ->addColumn('options_json', 'json', [
                'null' => true,
                'comment' => 'JSON array of options for select/radio/checkbox type questions.'
            ])
            ->addColumn('created_by', 'biginteger', [
                'signed' => false,
                'null' => true,
                'comment' => 'ID of the user who created the question.'
            ])
            ->addForeignKey('created_by', 'users', 'id', [
                'delete'=> 'SET_NULL'
            ])
            ->update();

        // Modify rating_type ENUM
        // Replace 'existing_value1', 'existing_value2' with your current values
        $this->table('evaluation_questions')
            ->changeColumn('rating_type', 'enum', [
                'values' => ['scale_5', 'scale_10', 'text_short', 'text_long', 'boolean', 'select_single', 'select_multiple'],
                'null' => false
            ])
            ->update();
    }

    public function down(): void
    {
        // Remove JSON column and created_by
        $this->table('evaluation_questions')
            ->dropForeignKey('created_by')
            ->removeColumn('options_json')
            ->removeColumn('created_by')
            ->update();

        // Revert rating_type ENUM changes
        $this->table('evaluation_questions')
            ->changeColumn('rating_type', 'enum', [
                'values' => ['scale_5', 'scale_10', 'text_short', 'text_long', 'boolean'],
                'null' => false
            ])
            ->update();
    }
}
