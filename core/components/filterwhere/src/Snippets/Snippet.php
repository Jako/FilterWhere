<?php
/**
 * Abstract Snippet
 *
 * @package FilterWhere
 * @subpackage snippet
 */

namespace TreehillStudio\FilterWhere\Snippets;

use modX;
use TreehillStudio\FilterWhere\FilterWhere;

/**
 * Class Snippet
 * @package FilterWhere
 */
abstract class Snippet
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    protected $modx;
    /**
     * A reference to the FilterWhere instance
     * @var FilterWhere $filterwhere
     */
    protected $filterwhere;
    /**
     * The snippet properties
     * @var array $properties
     */
    protected $properties = [];

    /**
     * The optional property prefix for snippet properties
     * @var string $propertyPrefix
     */
    protected $propertyPrefix = '';

    /**
     * Creates a new Snippet instance.
     *
     * @param modX $modx
     * @param array $properties
     */
    public function __construct(modX $modx, array $properties = [])
    {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('filterwhere.core_path', null, $this->modx->getOption('core_path') . 'components/filterwhere/');
        /** @var FilterWhere $filterwhere */
        $this->filterwhere = $this->modx->getService('filterwhere', 'FilterWhere', $corePath . 'model/filterwhere/', [
            'core_path' => $corePath
        ]);

        $this->properties = $this->initProperties($properties);
    }

    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties(): array
    {
        return [];
    }

    /**
     * @param array $properties
     * @return array
     */
    public function initProperties(array $properties = []): array
    {
        $result = [];
        foreach ($this->getDefaultProperties() as $key => $value) {
            $parts = explode('::', $key);
            $key = ($this->propertyPrefix && !in_array('noPrefix', $parts)) ? $this->propertyPrefix . ucfirst($parts[0]) : $parts[0];
            if (isset($parts[1]) && method_exists($this, 'get' . ucfirst($parts[1]))) {
                if (isset($parts[2])) {
                    $result[$parts[0]] = $this->{'get' . ucfirst($parts[1])}($this->modx->getOption($key, $properties, $value, true), $parts[2]);
                } else {
                    $result[$parts[0]] = $this->{'get' . ucfirst($parts[1])}($this->modx->getOption($key, $properties, $value, true));
                }
            } else {
                $result[$parts[0]] = $this->modx->getOption($key, $properties, $value, true);
            }
            unset($properties[$key]);
        }
        return array_merge($result, $properties);
    }

    /**
     * @param $value
     * @return int
     */
    protected function getInt($value): int
    {
        return (int)$value;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function getBool($value): bool
    {
        return ($value == 1 || $value == '1' || $value == true || $value == 'true');
    }

    /**
     * @param $value
     * @return array|null
     */
    protected function getAssociativeJson($value): ?array
    {
        return json_decode($value, true);
    }

    /**
     * Explode a separated value to an array.
     *
     * @param mixed $value
     * @param string $separator
     * @return array
     */
    protected function getExplodeSeparated($value, string $separator = ','): array
    {
        return (is_string($value) && $value !== '') ? array_map('trim', explode($separator, $value)) : [];
    }

    /**
     * Get the snippet properties.
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Get a snippet property value or the default value.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getProperty(string $key, $default = null)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }
        return $default;
    }

    abstract public function execute();
}