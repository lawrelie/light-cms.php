<?php
namespace Lawrelie\LightCms\Properties;
use Lawrelie\LightCms as llc;
use Throwable;
class Id extends Property implements \Stringable {
    const SEPARATOR = '/';
    public function __construct(private string $fromParent, llc\Cms $cms, private ?self $parent = null) {
        $this->fromParent = strtolower(trim(preg_replace(
            sprintf('/[^a-z0-9\-_\.~%s]/iu', preg_quote(self::SEPARATOR, '/')),
            '',
            strtr(mb_convert_kana($this->fromParent, 'a'), '\\', self::SEPARATOR),
        ), self::SEPARATOR));
        if (!!$this->parent && '' === $this->fromParent) {
            throw new Exception;
        }
        parent::__construct([], $cms);
    }
    public function __toString(): string {
        return $this->origin;
    }
    protected function getProperty_fromParent(): string {
        return $this->fromParent;
    }
    protected function getProperty_origin(): string {
        return (!$this->parent ? '' : $this->parent . self::SEPARATOR) . $this->fromParent;
    }
    protected function getProperty_parent(): ?self {
        return $this->parent;
    }
    public function from(string $from): string {
        if ($from === $this->origin) {
            return '';
        }
        $prefix = $from . self::SEPARATOR;
        if (!str_starts_with($this->origin, $prefix)) {
            throw new Exception;
        }
        return substr($this->origin, strlen($prefix));
    }
    public function to(string $to): string {
        if ($this->origin === $to) {
            return '';
        }
        $prefix = $this->origin . self::SEPARATOR;
        if (!str_starts_with($to, $prefix)) {
            throw new Exception;
        }
        return substr($to, strlen($prefix));
    }
}
