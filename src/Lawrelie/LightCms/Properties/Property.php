<?php
namespace Lawrelie\LightCms\Properties;
use Lawrelie\LightCms as llc;
class Property {
    use llc\GetProperty;
    public function __construct(iterable $instance, private llc\Cms $cms) {
        $this->setProperties($instance);
    }
    protected function getProperty_cms(): llc\Cms {
        return $this->cms;
    }
}
