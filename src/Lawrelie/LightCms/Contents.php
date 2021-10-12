<?php
namespace Lawrelie\LightCms;
use DateTimeInterface, PDO, Throwable;
class Contents {
    use GetProperty;
    public function __construct(iterable $instance, private Cms $cms, private ?self $parent = null, private string $path = '') {
        $this->setProperties($instance);
        if (!$this->cms->private && ($this->private || !!$this->date?->diff($this->cms->createDateTime())?->invert)) {
            throw new Exception;
        }
    }
    public function createChild(iterable|string $instance, ?string $className = null): self {
        return $this->cms->createContents($instance, $this, $className ?? static::class);
    }
    protected function getProperty_children(mixed $var): array {
        $children = [];
        try {
            foreach ($var as $v) {
                try {
                    $child = $this->createChild($v);
                    $children[(string) $child->id] = $child;
                } catch (Throwable) {}
            }
        } catch (Throwable) {}
        return $this->sortContents($children);
    }
    protected function getProperty_cms(): Cms {
        return $this->cms;
    }
    protected function getProperty_compare(): callable {
        try {
            $order = match ($this->inherit('order')) {'ASC' => 1, 'DESC' => -1, default => throw new Exception};
            $orderby_lc = strtolower(mb_convert_kana($this->inherit('orderby'), 'a'));
            [$compare, $orderby] = match ($orderby_lc) {
                'id' => ['strnatcmp', fn(self $contents): string => $contents->$orderby_lc],
                'name' => [[$this->cms->collator, 'compare'], fn(self $contents): string => $contents->$orderby_lc],
                'date', 'update' => [
                    function(?DateTimeInterface $a, ?DateTimeInterface $b): int {
                        try {
                            return $a->getTimestamp() - $b->getTimestamp();
                        } catch (Throwable) {}
                        return !$a ? (!$b ? 0 : 1) : -1;
                    }, fn(self $contents): ?DateTimeInterface => $contents->$orderby_lc,
                ],
                default => throw new Exception,
            };
            return fn(self $a, self $b): int => $compare($orderby($a), $orderby($b)) * $order;
        } catch (Throwable) {}
        return fn(self $a, self $b): int => 0;
    }
    protected function getProperty_content(mixed $var): string {
        try {
            if (!file_exists($var)) {
                throw new Exception;
            }
            $contents = file_get_contents($var);
            return false === $contents ? '' : $contents;
        } catch (Throwable) {}
        return '';
    }
    protected function getProperty_date(mixed $var): ?DateTimeInterface {
        if (!empty($var)) {
            try {
                return $var;
            } catch (Throwable) {}
            try {
                return $this->cms->createDateTime($var);
            } catch (Throwable) {}
        }
        return null;
    }
    protected function getProperty_description(mixed $var): string {
        return $this->getProperty_string($var);
    }
    protected function getProperty_id(mixed $var): Properties\Id {
        return $this->cms->createId($var, $this->parent?->id);
    }
    protected function getProperty_name(mixed $var): string {
        return $this->getProperty_string($var);
    }
    protected function getProperty_next(): ?self {
        try {
            $prev = null;
            foreach ($this->parent->children as $sibling) {
                try {
                    if ($this->is($prev)) {
                        return $sibling;
                    }
                } catch (Throwable) {}
                $prev = $sibling;
            }
        } catch (Throwable) {}
        return null;
    }
    protected function getProperty_order(mixed $var): string {
        try {
            $normalized = strtoupper(mb_convert_kana($var, 'a'));
            return match ($normalized) {'ASC', 'DESC' => $normalized, default => throw new Exception};
        } catch (Throwable) {}
        return !empty($var) ? $this->getProperty_string($var) : '';
    }
    protected function getProperty_orderby(mixed $var): string {
        return $this->getProperty_string($var);
    }
    protected function getProperty_parent(): ?self {
        return $this->parent;
    }
    protected function getProperty_path(): string {
        return $this->path;
    }
    protected function getProperty_prev(): ?self {
        try {
            $prev = null;
            foreach ($this->parent->children as $sibling) {
                if ($this->is($sibling)) {
                    return $prev;
                }
                $prev = $sibling;
            }
        } catch (Throwable) {}
        return null;
    }
    protected function getProperty_private(mixed $var): bool {
        return $this->getProperty_bool($var);
    }
    protected function getProperty_tags(mixed $var): array {
        $tags = $this->parent?->tags ?? [];
        try {
            foreach ($var as $v) {
                try {
                    $tag = $this->cms->tag->query($v);
                    $tag->parent->parent->parent;
                    $tags[(string) $tag->id] = $tag;
                    try {
                        $ancestor = $tag;
                        while (!!$ancestor->parent->parent->parent && !array_key_exists($ancestor->parent->id->origin, $tags)) {
                            $tags[$ancestor->parent->id->origin] = $ancestor->parent;
                            $ancestor = $ancestor->parent;
                        }
                    } catch (Throwable) {}
                } catch (Throwable) {}
            }
            usort($tags, [$this->cms, 'compareTags']);
            return $tags;
        } catch (Throwable) {}
        return $tags;
    }
    protected function getProperty_tagsFromParent(): array {
        try {
            return array_udiff($this->tags, $this->parent->tags, function(self $a, self $b): int {
                $result = $this->cms->compareTags($a, $b);
                return !$result ? strcmp($a->id, $b->id) : $result;
            });
        } catch (Throwable) {}
        return $this->tags;
    }
    protected function getProperty_update(mixed $var): ?DateTimeInterface {
        if (!empty($var)) {
            try {
                return $var;
            } catch (Throwable) {}
            try {
                return $this->cms->createDateTime($var);
            } catch (Throwable) {}
        }
        try {
            $this->cms->db->queryContentsUpdateByIdLike->execute([addcslashes($this->id . Properties\Id::SEPARATOR, '%_\\') . '%']);
            $row = $this->cms->db->queryContentsUpdateByIdLike->fetch();
            $this->cms->db->queryContentsUpdateByIdLike->closeCursor();
            return $this->cms->createDateTime($row['llc_update']);
        } catch (Throwable) {}
        try {
            $this->cms->db->queryContentsDateByIdLike->execute([addcslashes($this->id . Properties\Id::SEPARATOR, '%_\\') . '%']);
            $row = $this->cms->db->queryContentsDateByIdLike->fetch();
            $this->cms->db->queryContentsDateByIdLike->closeCursor();
            return $this->cms->createDateTime($row['llc_date']);
        } catch (Throwable) {}
        return $this->date;
    }
    public function index(): void {
        try {
            $this->cms->db->queryContentsId->execute([$this->id->origin]);
            $row = $this->cms->db->queryContentsId->fetch();
            $this->cms->db->queryContentsId->closeCursor();
            if (!!$row) {
                return;
            }
        } catch (Throwable) {}
        try {
            $this->cms->db->addContentsById->execute([$this->id->origin]);
            $this->cms->db->addContentsById->closeCursor();
        } catch (Throwable) {}
    }
    public function inherit(string $name): mixed {
        return !empty($this->$name) ? $this->$name : $this->parent?->inherit($name) ?? $this->$name;
    }
    public function is(self $contents): bool {
        return $this === $contents || (string) $this->id === (string) $contents->id;
    }
    public function query(string $query): ?self {
        try {
            $id = $this->cms->createId($query, $this->id);
        } catch (Throwable) {
            return null;
        }
        foreach ($this->children as $child) {
            if ($id->origin === $child->id->origin) {
                return $child;
            }
            try {
                return $child->query($child->id->to($id));
            } catch (Throwable) {}
        }
        return null;
    }
    public function sortContents(array $contents): array {
        usort($contents, $this->compare);
        return $contents;
    }
}
