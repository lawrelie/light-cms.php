<?php
namespace Lawrelie\LightCms;
use Throwable;
trait GetProperty {
    private array $_getProperties = [];
    private array $_setProperties = [];
    public function __get(string $name): mixed {
        if (array_key_exists($name, $this->_getProperties)) {
            return $this->_getProperties[$name];
        }
        $value = $this->_setProperties[$name] ?? null;
        $get = [$this, 'getProperty_' . $name];
        if (is_callable($get)) {
            $this->_getProperties[$name] = $get($value);
            return $this->_getProperties[$name];
        }
        $i = explode('_', $name)[0];
        $type = 'string';
        foreach (['array', 'bool', 'float', 'function', 'int', 'iterator', 'number', 'object', 'resource', 'scalar', 'string'] as $v) {
            if ($v === $i) {
                $type = $i;
                break;
            } else if ($v . 'Array' === $i) {
                $this->_getProperties[$name] = array_map([$this, 'getProperty_' . $v], $this->getProperty_array($value));
                return $this->_getProperties[$name];
            }
        }
        $this->_getProperties[$name] = [$this, 'getProperty_' . $type]($value);
        return $this->_getProperties[$name];
    }
    public function __isset(string $name): bool {
        try {
            return !is_null($this->__get($name));
        } catch (Throwable) {}
        return false;
    }
    protected function getProperty_array(mixed $var): array {
        try {
            return $var;
        } catch (Throwable) {}
        try {
            return iterator_to_array($var);
        } catch (Throwable) {}
        return [];
    }
    protected function getProperty_bool(mixed $var): bool {
        try {
            return $var;
        } catch (Throwable) {}
        try {
            return filter_var($var, FILTER_VALIDATE_BOOL);
        } catch (Throwable) {}
        return false;
    }
    protected function getProperty_float(mixed $var): float {
        try {
            return $var;
        } catch (Throwable) {}
        try {
            return filter_var($var, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC);
        } catch (Throwable) {}
        return 0;
    }
    protected function getProperty_int(mixed $var): int {
        return $this->getProperty_float($var);
    }
    protected function getProperty_iterator(mixed $var): iterable {
        return !is_iterable($var) ? $this->getProperty_array($var) : $var;
    }
    protected function getProperty_number(mixed $var): float|int|string {
        if (is_numeric($var)) {
            return $var;
        }
        try {
            $filtered = filter_var($var, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC);
            if (is_numeric($filtered)) {
                return $filtered;
            }
        } catch (Throwable) {}
        return $this->getProperty_float($var);
    }
    protected function getProperty_object(mixed $var): ?object {
        try {
            return $var;
        } catch (Throwable) {}
        return null;
    }
    protected function getProperty_resource(mixed $var): mixed {
        return !is_resource($var) ? null : $var;
    }
    protected function getProperty_scalar(mixed $var): bool|float|int|string {
        try {
            return $var;
        } catch (Throwable) {}
        return 0;
    }
    protected function getProperty_string(mixed $var): string {
        try {
            return $var;
        } catch (Throwable) {}
        return '';
    }
    public function setProperties(iterable $properties): void {
        foreach ($properties as $name => $value) {
            $this->_setProperties[$name] = $value;
        }
    }
}
