<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMoreStudentColumns extends AbstractMigration
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
    public function change(): void
    {
        $table = $this->table("students");
        $table->addColumn("ssnit_number", "string", [
            "null" => true,
            "limit" => 255,
            "after" => "account_number"
        ])
        ->addColumn("disability_status", "enum", [
            "default" => "no",
            "values" => ["no", "yes"],
            "null" => true,
            "limit" => 255,
            "after" => "ssnit_number"
        ])
        ->addColumn("disability_type", "string", [
            "null" => true,
            "limit" => 255,
            "after" => "disability_status"
        ]);
        $table->update();
    }
}
