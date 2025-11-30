<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeAdminTypeToUserRoles extends AbstractMigration
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
        $table = $this->table('admins');

        // 1. DROP the old foreign key (admin_types.id)
        // If your constraint name is unknown, you can drop by column:
        if ($table->hasForeignKey('type')) {
            $table->dropForeignKey('type');
        }

        // 2. OPTIONAL: If type needs to be unsigned / integer / etc. adjust it here
        // $table->changeColumn('type', 'integer', ['null' => false, 'signed' => false]);

        // 3. ADD the new foreign key (user_roles.id)
        $table->addForeignKey(
            'type',          // local column
            'user_roles',    // referenced table
            'id',            // referenced column
            [
                'delete' => 'NO_ACTION',
                'update' => 'NO_ACTION',
            ]
        );

        // Save changes
        $table->update();
    }
}
