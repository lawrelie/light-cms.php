<?php
namespace Lawrelie\LightCms\Properties;
use Lawrelie\LightCms as llc;
use PDO, PDOStatement, Throwable;
class Database extends Property {
    public function __construct(private PDO $_origin, llc\Cms $cms) {
        parent::__construct([], $cms);
    }
    protected function getProperty_origin(): PDO {
        $this->_origin->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        try {
            $this->_origin->beginTransaction();
            $this->_origin->exec('CREATE TABLE IF NOT EXISTS llc_contents (llc_id TEXT PRIMARY KEY UNIQUE)');
            $columns = [
                'TEXT PRIMARY KEY UNIQUE' => ['id'],
                'TEXT DEFAULT NULL' => ['date', 'instance', 'path', 'update'],
                'INT DEFAULT NULL' => ['mtime'],
            ];
            foreach ($columns as $def => $names) {
                foreach ($names as $name) {
                    try {
                        $this->_origin->exec("ALTER TABLE llc_contents ADD llc_$name $def");
                    } catch (Throwable) {}
                }
            }
            $this->_origin->commit();
        } catch (Throwable) {
            $this->_origin->rollBack();
        }
        return $this->_origin;
    }
    protected function getProperty_addContentsById(): PDOStatement {
        return $this->origin->prepare('INSERT INTO llc_contents (llc_id) VALUES (?)');
    }
    protected function getProperty_addContentsDateById(): PDOStatement {
        return $this->origin->prepare(
            'UPDATE llc_contents
            SET llc_date = :llc_date, llc_update = :llc_update
            WHERE llc_id IS :llc_id AND (llc_date IS NOT :llc_date OR llc_update IS NOT :llc_update)'
        );
    }
    protected function getProperty_addContentsInstanceFromFileById(): PDOStatement {
        return $this->origin->prepare('UPDATE llc_contents SET llc_instance = :llc_instance, llc_mtime = :llc_mtime, llc_path = :llc_path WHERE llc_id IS :llc_id');
    }
    protected function getProperty_queryContentsDateByIdLike(): PDOStatement {
        return $this->origin->prepare('SELECT llc_date FROM llc_contents WHERE llc_date NOT NULL AND llc_id LIKE ? ORDER BY llc_date DESC LIMIT 1');
    }
    protected function getProperty_queryContentsId(): PDOStatement {
        return $this->origin->prepare('SELECT llc_id FROM llc_contents WHERE llc_id IS ?');
    }
    protected function getProperty_queryContentsInstanceByPathWithMtime(): PDOStatement {
        return $this->origin->prepare('SELECT llc_instance FROM llc_contents WHERE llc_path IS :llc_path AND llc_mtime NOT NULL AND llc_mtime > :llc_mtime ORDER BY llc_mtime DESC LIMIT 1');
    }
    protected function getProperty_queryContentsUpdateByIdLike(): PDOStatement {
        return $this->origin->prepare('SELECT llc_update FROM llc_contents WHERE llc_update NOT NULL AND llc_id LIKE ? ORDER BY llc_update DESC LIMIT 1');
    }
    protected function getProperty_removeContentsByPath(): PDOStatement {
        return $this->origin->prepare('DELETE FROM llc_contents WHERE llc_path IS ?');
    }
    protected function getProperty_removeContentsPathById(): PDOStatement {
        return $this->origin->prepare('UPDATE llc_contents SET llc_path = :llc_path WHERE llc_id IS :llc_id AND llc_path IS NOT :llc_path');
    }
}
