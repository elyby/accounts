<?php
/**
 * @var string $className the new migration class name
 */

echo "<?php\n";
?>
declare(strict_types=1);

use console\db\Migration;

final class <?= $className; ?> extends Migration {

    public function safeUp(): void {

    }

    public function safeDown(): void {

    }

}
