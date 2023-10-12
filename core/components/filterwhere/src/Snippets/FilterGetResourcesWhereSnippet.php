<?php
/**
 * FilterGetResourcesWhere Snippet
 *
 * @package filterwhere
 * @subpackage snippet
 */

namespace TreehillStudio\FilterWhere\Snippets;

use DateTimeImmutable;
use Exception;
use TreehillStudio\FilterWhere\Helper\Geocode;
use xPDO;

class FilterGetResourcesWhereSnippet extends Snippet
{
    public $values;

    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'fields::associativeJson' => '',
            'where::associativeJson' => '',
            'emptyRedirect' => '',
            'toPlaceholder' => '',
            'varName::allowedVarName' => 'REQUEST',
            'type' => 'where'
        ];
    }

    /**
     * Execute the snippet and return the result.
     *
     * @return string
     * @throws /Exception
     */
    public function execute()
    {
        $where = $this->getProperty('where');
        if (!$where) {
            $where = [];
        }
        $emptyRedirect = $this->getProperty('emptyRedirect');
        $toPlaceholder = $this->getProperty('toPlaceholder');
        $fields = $this->getProperty('fields');
        $type = $this->getProperty('type');

        switch ($this->getProperty('varName')) {
            case 'GET':
                $this->values = $_GET;
                break;
            case 'POST':
                $this->values = $_POST;
                break;
            case 'REQUEST':
                $this->values = $_REQUEST;
        }

        // URL parameter
        $idx = 0;
        foreach ($fields as $key => $field) {
            $field = explode('::', $field);
            $value = $this->modx->getOption($key, $this->values, false);
            $phValue = ($value) ? $this->stripTags($value) : '';
            if ($type == 'having') {
                $operator = $field[1] ?: '=';
                $junction = $field[2] ?: '';
                $junction = ($idx && !$junction) ? 'AND' : $junction;
            } else {
                $operator = $field[1] ? ':' . $field[1] : ':=';
                $junction = $field[2] ? $field[2] . ':' : '';
            }

            if (in_array($field[1], ['GEOCODE'])) {
                $subwhere = [];
                if ($type == 'having') {
                    $this->setHaving($subwhere, $key, $field[0], $value, $phValue, $operator, $subjunction);
                } else {
                    $this->setWhere($subwhere, $key, $field[0], $value, $phValue, $operator, $subjunction);
                }
                if (count($subwhere)) {
                    $where[] = $subwhere[0];
                }
            } else {
                $subfields = explode(',', $field[0]);
                $subwhere = [];
                $subjunction = '';
                foreach ($subfields as $subfield) {
                    if ($type == 'having') {
                        $this->setHaving($subwhere, $key, $subfield, $value, $phValue, $operator, $subjunction);
                        $subjunction = 'OR';
                    } else {
                        $this->setWhere($subwhere, $key, $subfield, $value, $phValue, $operator, $subjunction);
                        $subjunction = 'OR:';
                    }
                }
                if (!empty($subwhere)) {
                    if ($type == 'having') {
                        if (count($subwhere) > 1) {
                            $where[] = $junction . ' (' . implode(' ', $subwhere) . ')';
                        } else {
                            $where[] = $junction . ' ' . implode(' ', $subwhere);
                        }
                    } else {
                        $where[] = $subwhere;
                    }
                    $idx++;
                }
            }
        }

        if (empty($where) && $emptyRedirect) {
            $this->modx->sendRedirect($this->modx->makeUrl($emptyRedirect));
        }

        if ($type == 'having') {
            $output = ($where) ? '["' . trim(implode(' ', $where)) . '"]' : '';
        } else {
            $output = str_replace(['[[', ']]'], ['[ [', '] ]'], json_encode($where));
        }

        if ($toPlaceholder) {
            $this->modx->setPlaceholder($toPlaceholder, $output);
            $output = '';
        }
        return $output;
    }

    /**
     * Recursive wrapper for $modx->stripTags
     *
     * @param array|string $value
     * @return array|string
     */
    private function stripTags($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->stripTags($v);
            }
        } else {
            $value = $this->modx->stripTags($value);
            $value = htmlspecialchars($value);
        }
        return $value;
    }

    /**
     * Add a value to the where clause and set a filtered placeholder
     *
     * @param array $having
     * @param string $option
     * @param string $field
     * @param string|array $value
     * @param string|array|null $phValue
     * @param string $operator
     * @param string $junction
     */
    private function setHaving(array &$having, string $option, string $field, $value, $phValue = null, string $operator = '=', string $junction = '')
    {
        $phValue = is_null($phValue) ? $value : $phValue;
        if ($value !== false && $value !== '') {
            if (is_array($value)) {
                if ($operator == '=' || $operator == 'IN') {
                    $having[] = "$junction $field IN (" . implode(',', array_map(function ($string) {
                            return "'$string'";
                        }, $value)) . ")";
                    $this->modx->setPlaceholder($option . '_value', json_encode($phValue));
                } else {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Operator can\'t be different than `=` or `IN` with an array value');
                }
            } else {
                if ($operator == 'LIKE') {
                    $having[] = "$junction $field $operator '%$value%'";
                } elseif ($operator == 'RANGE') {
                    $range = array_map('trim', explode('-', $value));
                    if (isset($range[0]) && $range[0] != '' && isset($range[1]) && $range[1] != '') {
                        $having[] = "$junction ($field >= '$range[0]' AND $field < '$range[1]')";
                    } elseif (!empty($range[0]) && strpos($value, '-') !== false) {
                        $having[] = "$junction $field >= $range[0]";
                    } elseif (isset($range[1]) && !empty($range[1])) {
                        $having[] = "$junction $field < $range[1]";
                    } else {
                        $having[] = $junction . ' 0 = 1';
                    }
                } elseif ($operator == 'DATE') {
                    try {
                        $start = new DateTimeImmutable($value);
                        $end = $start->modify('+1 day');
                        $date = new DateTimeImmutable($value);
                        $having[] = "$junction $field >= {$start->format('Y-m-d H:i:s')} AND $field < {$end->format('Y-m-d H:i:s')}";
                    } catch (Exception $e) {
                    }
                } elseif ($operator == 'GEOCODE') {
                    $geolocation = new Geocode($this->modx);
                    try {
                        $location = $geolocation->geocode($value);
                        $distance = $this->filterwhere->getOption('distance', $this->values, '');
                        $locationFields = explode('||', $field);
                        if ($location && $distance && count($locationFields) > 1) {
                            $locality = $location->first()->getCoordinates();
                            $latitude = number_format($locality->getLatitude(), 10, '.', '');
                            $longitude = number_format($locality->getLongitude(), 10, '.', '');
                            $having[] = $junction . " ROUND(3959 * acos(" .
                                "cos(radians($latitude)) * " .
                                "cos(radians($locationFields[0])) * " .
                                "cos(radians($locationFields[1]) - radians($longitude)) + " .
                                "sin(radians($latitude)) * " .
                                "sin(radians($locationFields[0]))" .
                                "), 2) < $distance";
                        }
                        $this->modx->setPlaceholder('distance_value', $distance);
                    } catch (Exception $e) {
                        $having[] = ['0 = 1'];
                    }
                } else {
                    $having[] = "$junction $field $operator '$value'";
                }
                $this->modx->setPlaceholder($option . '_value', $phValue);
            }
        }
    }

    /**
     * Add a value to the where clause and set a filtered placeholder
     *
     * @param array $where
     * @param string $option
     * @param string $field
     * @param string|array $value
     * @param string|array|null $phValue
     * @param string $operator
     * @param string $junction
     */
    private function setWhere(array &$where, string $option, string $field, $value, $phValue = null, string $operator = ':=', string $junction = '')
    {
        $phValue = is_null($phValue) ? $value : $phValue;
        if ($value !== false && $value !== '') {
            if (is_array($value)) {
                if ($operator == ':=' || $operator == ':IN') {
                    $where[$junction . $field . ':IN'] = $value;
                    $this->modx->setPlaceholder($option . '_value', json_encode($phValue));
                } else {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Operator can\'t be different than `=` or `IN` with an array value');
                }
            } else {
                if ($operator == ':LIKE') {
                    $where[$junction . $field . $operator] = '%' . $value . '%';
                } elseif ($operator == ':RANGE') {
                    $range = array_map('trim', explode('-', $value));
                    if (isset($range[0]) && $range[0] != '' && isset($range[1]) && $range[1] != '') {
                        $where[] = [
                            $junction . $field . ':>=' => $range[0],
                            'AND:' . $field . ':<' => $range[1],
                        ];
                    } elseif (!empty($range[0]) && strpos($value, '-') !== false) {
                        $where[$junction . $field . ':>='] = $range[0];
                    } elseif (isset($range[1]) && !empty($range[1])) {
                        $where[$junction . $field . ':<'] = $range[1];
                    } else {
                        $where[] = '0 = 1';
                    }
                } elseif ($operator == ':DATE') {
                    try {
                        $start = new DateTimeImmutable($value);
                        $end = $start->modify('+1 day');
                        $where[] = [
                            $junction . $field . ':>=' => $start->format('Y-m-d H:i:s'),
                            $field . ':<' => $end->format('Y-m-d H:i:s')
                        ];
                    } catch (Exception $e) {
                        $where[] = ['0 = 1'];
                    }
                } elseif ($operator == ':GEOCODE') {
                    $geolocation = new Geocode($this->modx);
                    try {
                        $location = $geolocation->geocode($value);
                        $distance = (int)$this->filterwhere->getOption('distance', $this->values, '');
                        $locationFields = explode('||', $field);
                        if ($location && $distance && count($locationFields) > 1) {
                            $locality = $location->first()->getCoordinates();
                            $latitude = number_format($locality->getLatitude(), 10, '.', '');
                            $longitude = number_format($locality->getLongitude(), 10, '.', '');
                            $where[] = [
                                "ROUND(3959 * acos(" .
                                "cos(radians($latitude)) * " .
                                "cos(radians($locationFields[0])) * " .
                                "cos(radians($locationFields[1]) - radians($longitude)) + " .
                                "sin(radians($latitude)) * " .
                                "sin(radians($locationFields[0]))" .
                                "), 2) < $distance"
                            ];
                        }
                    } catch (Exception $e) {
                        $where[] = ['0 = 1'];
                    }
                    $this->modx->setPlaceholder('distance_value', $distance);
                } else {
                    $where[$junction . $field . $operator] = $value;
                }
                $this->modx->setPlaceholder($option . '_value', $phValue);
            }
        }
    }

    /**
     * @param $value
     * @return string
     */
    public function getAllowedVarName($value): string
    {
        if (in_array(strtoupper($value), ['REQUEST', 'GET', 'POST'])) {
            return $value;
        } else {
            return 'REQUEST';
        }
    }
}
