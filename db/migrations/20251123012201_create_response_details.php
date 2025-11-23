<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateResponseDetails extends AbstractMigration
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
        $table = $this->table('response_details', ['id' => false, 'primary_key' => ['id']]);
        
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
              ->addColumn('response_id', 'biginteger', ['signed' => false])
              ->addColumn('question_id', 'biginteger', ['signed' => false])
              
              // Preservation of Original Question Text
              ->addColumn('question_text_snapshot', 'text', ['comment' => 'Preserved question text at the time of response.'])
              
              // Answer Data
              ->addColumn('answer_value', 'integer', ['null' => true, 'comment' => 'Numeric rating (e.g., 1-5).'])
              ->addColumn('answer_text', 'text', ['null' => true, 'comment' => 'Free-form text/comments.'])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              
              // Constraints
              ->addIndex(['response_id', 'question_id'], ['unique' => true, 'name' => 'idx_unique_answer_per_question']) // Ensures only one answer per question per response
              
              // Foreign Keys
              ->addForeignKey('response_id', 'evaluation_responses', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('question_id', 'evaluation_questions', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
              ->create();
    }
}
