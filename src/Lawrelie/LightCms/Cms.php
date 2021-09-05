<?php
namespace Lawrelie\LightCms;
use Collator, DateTimeInterface, DateTimeZone, Locale, PDO, Throwable;
class Cms {
    use GetProperty;
    public function __construct(iterable $instance, private string $charset = 'UTF-8') {
        if (in_array(false, [ini_set('default_charset', $this->charset), mb_internal_encoding($this->charset)], true)) {
            throw new Exception;
        }
        $this->setProperties($instance);
    }
    public function compareTags(Contents $a, Contents $b): int {
        try {
            return '' === $a->id->from($b->id) ? 0 : 1;
        } catch (Throwable) {}
        try {
            return '' === $a->id->to($b->id) ? 0 : -1;
        } catch (Throwable) {}
        $aa = $bb = null;
        $intersection = $a;
        try {
            while (true) {
                try {
                    $intersection->id->to($b->id);
                    break;
                } catch (Throwable) {}
                $intersection = $intersection->parent;
                if (!$intersection) {
                    throw new Exception;
                }
            }
            foreach ($intersection->children as $child) {
                try {
                    if ($child->id->to($a->id)) {
                        $aa = $child;
                    }
                } catch (Throwable) {}
                try {
                    if ($child->id->to($b->id)) {
                        $bb = $child;
                    }
                } catch (Throwable) {}
                if (!!$aa && !!$bb) {
                    break;
                }
            }
        } catch (Throwable) {}
        return ($this->tag->compare)($aa ?? $a, $bb ?? $b);
    }
    public function createContents(iterable|string $instance, Contents $parent = null, string $className = Contents::class): Contents {
        try {
            $contents = new $className($instance, $this, $parent);
            if (!($contents instanceof Contents)) {
                throw new Exception;
            }
            try {
                $this->db->removeContentsPathById->execute([(string) $contents->id]);
                $this->db->removeContentsPathById->closeCursor();
            } catch (Throwable) {}
            $contents->index();
            try {
                foreach (['date', 'update'] as $name) {
                    $this->db->addContentsDateById->bindValue(':llc_' . $name, $contents->$name?->format('c'), !$contents->$name ? PDO::PARAM_NULL : PDO::PARAM_STR);
                }
                $this->db->addContentsDateById->bindValue(':llc_id', $contents->id->origin, PDO::PARAM_STR);
                $this->db->addContentsDateById->execute();
                $this->db->addContentsDateById->closeCursor();
            } catch (Throwable) {}
            return $contents;
        } catch (Throwable) {}
        if (!file_exists($instance)) {
            throw new Exception;
        }
        $path = realpath($instance);
        try {
            $this->db->queryContentsInstanceByPathWithMtime->bindValue(':llc_mtime', filemtime($path), PDO::PARAM_INT);
            $this->db->queryContentsInstanceByPathWithMtime->bindValue(':llc_path', $path, PDO::PARAM_STR);
            $this->db->queryContentsInstanceByPathWithMtime->execute();
            $row = $this->db->queryContentsInstanceByPathWithMtime->fetch();
            $this->db->queryContentsInstanceByPathWithMtime->closeCursor();
            return new $className(unserialize($row['llc_instance']), $this, $parent, $path);
        } catch (Throwable) {}
        try {
            $included = include $path;
            $contents = new $className($included, $this, $parent, $path);
            if (!($contents instanceof Contents)) {
                throw new Exception;
            }
        } catch (Throwable) {
            try {
                $this->db->removeContentsByPath->execute([$path]);
                $this->db->removeContentsByPath->closeCursor();
            } catch (Throwable) {}
            throw $e;
        }
        $contents->index();
        try {
            foreach (['date', 'update'] as $name) {
                $this->db->addContentsDateById->bindValue(':llc_' . $name, $contents->$name?->format('c'), !$contents->$name ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }
            $this->db->addContentsDateById->bindValue(':llc_id', $contents->id->origin, PDO::PARAM_STR);
            $this->db->addContentsDateById->execute();
            $this->db->addContentsDateById->closeCursor();
        } catch (Throwable) {}
        try {
            $this->db->addContentsInstanceFromFileById->bindValue(':llc_id', $contents->id->origin, PDO::PARAM_STR);
            $this->db->addContentsInstanceFromFileById->bindValue(':llc_instance', serialize($included), PDO::PARAM_STR);
            $this->db->addContentsInstanceFromFileById->bindValue(':llc_mtime', time(), PDO::PARAM_INT);
            $this->db->addContentsInstanceFromFileById->bindValue(':llc_path', $path, PDO::PARAM_STR);
            $this->db->addContentsInstanceFromFileById->execute();
            $this->db->addContentsInstanceFromFileById->closeCursor();
        } catch (Throwable) {}
        return $contents;
    }
    public function createDatabase(PDO $db, string $className = Properties\Database::class): Properties\Database {
        return new $className($db, $this);
    }
    public function createDateTime(string $datetime = 'now'): DateTimeInterface {
        $result = date_create_immutable($datetime, $this->timezone);
        try {
            return $result->setTimezone($this->timezone);
        } catch (Throwable) {}
        return $result;
    }
    public function createId(string $fromParent, ?Properties\Id $parent = null, string $className = Properties\Id::class): Properties\Id {
        return new $className($fromParent, $this, $parent);
    }
    protected function getProperty_charset(): string {
        return $this->charset;
    }
    protected function getProperty_collator(): ?Collator {
        try {
            $collator = Collator::create($this->locale);
            $collator->setAttribute(Collator::ALTERNATE_HANDLING, Collator::SHIFTED);
            $collator->setStrength(Collator::PRIMARY);
            return $collator;
        } catch (Throwable) {}
        return null;
    }
    protected function getProperty_contents(): Contents {
        try {
            $queried = $this->index->query($this->query);
            if (!!$queried) {
                return $queried;
            }
        } catch (Throwable) {}
        return $this->index;
    }
    protected function getProperty_db(mixed $var): ?Properties\Database {
        try {
            return $this->createDatabase($var);
        } catch (Throwable) {}
        return null;
    }
    protected function getProperty_index(mixed $var): Contents {
        return $this->createContents($var);
    }
    protected function getProperty_locale(mixed $var): string {
        return !is_iterable($var) ? Locale::canonicalize($this->getProperty_string($var)) : Locale::composeLocale($this->getProperty_array($var));
    }
    protected function getProperty_private(mixed $var): bool {
        return $this->getProperty_bool($var);
    }
    protected function getProperty_query(mixed $var): string {
        return $this->getProperty_string($var);
    }
    protected function getProperty_tag(mixed $var): Contents {
        return $this->createContents($var);
    }
    protected function getProperty_timezone(mixed $var): ?DateTimeZone {
        try {
            return $var;
        } catch (Throwable) {}
        return null;
    }
}
