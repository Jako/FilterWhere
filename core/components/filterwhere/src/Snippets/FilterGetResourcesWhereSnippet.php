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
    /** @var array $values */
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
            'options::associativeJson' => '',
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
                $junction = (!empty($field[2])) ? $field[2] : '';
                $junction = ($idx && !$junction) ? 'AND' : $junction;
            } else {
                $operator = $field[1] ? ':' . $field[1] : ':=';
                $junction = (!empty($field[2])) ? $field[2] . ':' : '';
            }

            if (in_array($field[1], ['GEOCODE', 'DATERANGE'])) {
                $subwhere = [];
                $subjunction = '';
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
                switch ($operator) {
                    case 'LIKE':
                        $having[] = $this->havingLike($junction, $field, $operator, $value);
                        break;
                    case 'RANGE':
                        $having[] = $this->havingRange($junction, $field, $value);
                        break;
                    case 'DATE':
                        $having[] = $this->havingDate($junction, $field, $value);
                        break;
                    case 'DATERANGE':
                        $having[] = $this->havingDaterange($junction, $field, $value);
                        break;
                    case 'GEOCODE':
                        $having[] = $this->havingGeocode($junction, $field, $value);
                        break;
                    default:
                        $having[] = $this->havingValue($junction, $field, $operator, $value);
                        break;
                }
                $this->modx->setPlaceholder($option . '_value', $phValue);
            }
        }
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return string
     */
    private function havingLike(string $junction, string $field, string $operator, string $value): string
    {
        return "$junction $field $operator '%$value%'";
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return string
     */
    private function havingRange(string $junction, string $field, string $value): string
    {
        $separator = $this->modx->getOption('rangeseparator', $this->getProperty('options'), '-', true);
        $range = array_map('trim', explode($separator, $value));
        $start = (isset($range[0]) && $range[0] != '') ? intval($range[0]) : null;
        $end = (isset($range[1]) && $range[1] != '') ? intval($range[1]) : null;
        if (!is_null($start) && !is_null($end)) {
            $having = "$junction ($field >= $start AND $field < $end)";
        } elseif (!is_null($start)) {
            $having = "$junction $field >= $start";
        } elseif (!is_null($end)) {
            $having = "$junction $field < $end";
        } else {
            $having = $junction . ' 0 = 1';
        }
        return $having;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return string
     */
    private function havingDate(string $junction, string $field, string $value): string
    {
        try {
            $format = $this->modx->getOption('dateformat', $this->getProperty('options'), 'Y-m-d H:i:s', true);
            $start = new DateTimeImmutable($value);
            $end = $start->modify('+1 day');
            $start = ($format === 'unixtime') ? $start->getTimestamp() : $start->format($format);
            $end = ($format === 'unixtime') ? $end->getTimestamp() : $end->format($format);
            $having = "$junction ($field >= $start AND $field < $end)";
        } catch (Exception $e) {
            $having = $junction . ' 0 = 1';
        }
        return $having;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return string
     */
    private function havingDaterange(string $junction, string $field, string $value): string
    {
        try {
            $separator = $this->modx->getOption('daterangeseparator', $this->getProperty('options'), '-', true);
            $format = $this->modx->getOption('dateformat', $this->getProperty('options'), 'Y-m-d H:i:s', true);
            $daterange = array_map('trim', explode($separator, $value));
            $start = (!empty($daterange[0])) ? new DateTimeImmutable($daterange[0]) : null;
            $end = (!empty($daterange[1])) ? new DateTimeImmutable($daterange[1]) : null;
            $start = ($start) ? (($format === 'unixtime') ? $start->getTimestamp() : $start->format($format)) : $start;
            $end = ($end) ? (($format === 'unixtime') ? $end->getTimestamp() : $end->format($format)) : $end;
            if ($start && $end) {
                $having = "$junction ($field >= $start AND $field < $end)";
            } elseif ($start) {
                $having = "$junction $field >= $start";
            } elseif ($end) {
                $having = "$junction $field < $end";
            } else {
                $having = $junction . ' 0 = 1';
            }
        } catch (Exception $e) {
            $having = $junction . ' 0 = 1';
        }
        return $having;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return string
     */
    private function havingGeocode(string $junction, string $field, string $value): string
    {
        $geolocation = new Geocode($this->modx);
        try {
            $location = $geolocation->geocode($value);
            $distance = intval($this->filterwhere->getOption('distance', $this->values, $this->modx->getOption('distance', $this->getProperty('options'), '0', true)));
            $locationFields = explode('||', $field);
            if ($location && $distance && count($locationFields) > 1) {
                $locality = $location->first()->getCoordinates();
                $latitude = number_format($locality->getLatitude(), 10, '.', '');
                $longitude = number_format($locality->getLongitude(), 10, '.', '');
                $having = $junction . " ROUND(3959 * acos(" .
                    "cos(radians($latitude)) * " .
                    "cos(radians($locationFields[0])) * " .
                    "cos(radians($locationFields[1]) - radians($longitude)) + " .
                    "sin(radians($latitude)) * " .
                    "sin(radians($locationFields[0]))" .
                    "), 2) < $distance";
            } else {
                $having = '';
            }
            $this->modx->setPlaceholder('distance_value', $distance);
        } catch (\Geocoder\Exception\Exception $e) {
            if ($this->filterwhere->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Geocoder issue: ' . $e->getMessage());
            }
            $having = $junction . ' 0 = 1';
        }
        return $having;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return string
     */
    private function havingValue(string $junction, string $field, string $operator, string $value): string
    {
        return "$junction $field $operator '$value'";
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
                switch ($operator) {
                    case ':LIKE':
                        $where[] = $this->whereLike($junction, $field, $operator, $value);
                        break;
                    case ':RANGE':
                        $where[] = $this->whereRange($junction, $field, $value);
                        break;
                    case ':DATE':
                        $where[] = $this->whereDate($junction, $field, $value);
                        break;
                    case ':DATERANGE':
                        $where[] = $this->whereDaterange($junction, $field, $value);
                        break;
                    case ':GEOCODE':
                        $where[] = $this->whereGeocode($junction, $field, $value);
                        break;
                    default:
                        $where[] = $this->whereValue($junction, $field, $operator, $value);
                        break;
                }
                $this->modx->setPlaceholder($option . '_value', $phValue);
            }
        }
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return array
     */
    private function whereLike(string $junction, string $field, string $operator, string $value): array
    {
        $value = str_replace(' ', '%', $value);
        return [$junction . $field . $operator => '%' . $value . '%'];
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return array
     */
    private function whereRange(string $junction, string $field, string $value): array
    {
        $separator = $this->modx->getOption('rangeseparator', $this->getProperty('options'), '-', true);
        $range = array_map('trim', explode($separator, $value));
        $start = (isset($range[0]) && $range[0] != '') ? intval($range[0]) : null;
        $end = (isset($range[1]) && $range[1] != '') ? intval($range[1]) : null;
        if (!is_null($start) && !is_null($end)) {
            $where = [
                $junction . $field . ':>=' => $start,
                'AND:' . $field . ':<' => $end,
            ];
        } elseif (!is_null($start)) {
            $where = [$junction . $field . ':>=' => $start];
        } elseif (!is_null($end)) {
            $where = [$junction . $field . ':<' => $end];
        } else {
            $where = ['0 = 1'];
        }
        return $where;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return array
     */
    private function whereDate(string $junction, string $field, string $value): array
    {
        try {
            $format = $this->modx->getOption('dateformat', $this->getProperty('options'), 'Y-m-d H:i:s', true);
            $start = new DateTimeImmutable($value);
            $end = $start->modify('+1 day');
            $start = ($format === 'unixtime') ? $start->getTimestamp() : $start->format($format);
            $end = ($format === 'unixtime') ? $end->getTimestamp() : $end->format($format);
            $where = [
                $junction . $field . ':>=' => $start,
                $field . ':<' => $end
            ];
        } catch (Exception $e) {
            $where = ['0 = 1'];
        }
        return $where;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return array
     */
    private function whereDaterange(string $junction, string $field, string $value): array
    {
        try {
            $separator = $this->modx->getOption('daterangeseparator', $this->getProperty('options'), '-', true);
            $format = $this->modx->getOption('dateformat', $this->getProperty('options'), 'Y-m-d H:i:s', true);
            $daterange = array_map('trim', explode($separator, $value));
            $start = (!empty($daterange[0])) ? new DateTimeImmutable($daterange[0]) : null;
            $end = (!empty($daterange[1])) ? new DateTimeImmutable($daterange[1]) : null;
            $start = ($start) ? (($format === 'unixtime') ? $start->getTimestamp() : $start->format($format)) : $start;
            $end = ($end) ? (($format === 'unixtime') ? $end->getTimestamp() : $end->format($format)) : $end;
            if ($start && $end) {
                $where = [
                    $junction . $field . ':>=' => $start,
                    'AND:' . $field . ':<' => $end,
                ];
            } elseif ($start) {
                $where = [
                    $junction . $field . ':>=' => $start,
                ];
            } elseif ($end) {
                $where = [
                    $junction . $field . ':<' => $end,
                ];
            } else {
                $where = ['0 = 1'];
            }
        } catch (Exception $e) {
            $where = ['0 = 1'];
        }
        return $where;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $value
     * @return array
     */
    private function whereGeocode(string $junction, string $field, string $value): array
    {
        $geolocation = new Geocode($this->modx);
        $distance = 0;
        try {
            $location = $geolocation->geocode($value);
            $distance = intval($this->filterwhere->getOption('distance', $this->values, ''));
            $locationFields = explode('||', $field);
            if ($location && $distance && count($locationFields) > 1) {
                $locality = $location->first()->getCoordinates();
                $latitude = number_format($locality->getLatitude(), 10, '.', '');
                $longitude = number_format($locality->getLongitude(), 10, '.', '');
                $where = [
                    rtrim($junction, ':') ." ROUND(3959 * acos(" .
                    "cos(radians($latitude)) * " .
                    "cos(radians($locationFields[0])) * " .
                    "cos(radians($locationFields[1]) - radians($longitude)) + " .
                    "sin(radians($latitude)) * " .
                    "sin(radians($locationFields[0]))" .
                    "), 2) < $distance"
                ];
            } else {
                $where = [];
            }
        } catch (\Geocoder\Exception\Exception $e) {
            if ($this->filterwhere->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Geocoder issue: ' . $e->getMessage());
            }
            $where = ['0 = 1'];
        }
        $this->modx->setPlaceholder('distance_value', $distance);
        return $where;
    }

    /**
     * @param string $junction
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return array
     */
    private function whereValue(string $junction, string $field, string $operator, string $value): array
    {
        return [$junction . $field . $operator => $value];
    }

    /**
     * @param $value
     * @return string
     */
    public function getAllowedVarName($value)
    {
        if (in_array(strtoupper($value), ['REQUEST', 'GET', 'POST'])) {
            return $value;
        } else {
            return 'REQUEST';
        }
    }
}
