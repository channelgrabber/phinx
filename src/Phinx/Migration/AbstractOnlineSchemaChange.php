<?php
namespace Phinx\Migration;

use Phinx\Db\Adapter\MysqlAdapter;

/**
 * @property MysqlAdapter $adapter
 */
abstract class AbstractOnlineSchemaChange extends AbstractMigration
{
    protected function onlineSchemaChange($table, $alter, $maxThreads = 50)
    {
        $options = $this->adapter->getOptions();
        $config = require getcwd() . '/config/storage.local.php';
        if (!isset($config[$options['name']])) {
            throw new \InvalidArgumentException('Root access is not configured for database ' . $options['name']);
        }

        $dsn = [
            'h=' . addcslashes($config[$options['name']]['hostname'], ','),
            'u=' . addcslashes($config[$options['name']]['username'], ','),
            'p=' . addcslashes($config[$options['name']]['password'], ','),
            'D=' . addcslashes($config[$options['name']]['database'], ','),
            't=' . addcslashes($table, ','),
        ];

        if (isset($config[$options['name']]['port'])) {
            $dsn[] = 'P=' . addcslashes($config[$options['name']]['port'], ',');
        }
        if (isset($config[$options['name']]['unix_socket'])) {
            $dns[] = 'S=' . addcslashes($config[$options['name']]['unix_socket'], ',');
        }
        if (isset($config[$options['name']]['charset'])) {
            $dns[] = 'A=' . addcslashes($config[$options['name']]['charset'], ',');
        }

        $command = 'pt-online-schema-change';
        $arguments = [
            '--execute',
            '--alter ' . escapeshellarg($alter),
            '--alter-foreign-keys-method ' . escapeshellarg('auto'),
            '--critical-load Threads_running=' . escapeshellarg($maxThreads),
            escapeshellarg(implode(',', $dsn)),
        ];

        passthru($command . ' ' . implode(' ', $arguments), $return);
        if ($return !== 0) {
            throw new \RuntimeException('Failed to update ' . $table);
        }
    }
}
