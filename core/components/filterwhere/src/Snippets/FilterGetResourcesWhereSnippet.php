<?php
/**
 * FilterGetResourcesWhere Snippet
 *
 * @package filterwhere
 * @subpackage snippet
 */

namespace TreehillStudio\FilterWhere\Snippets;

use xPDO;

class FilterGetResourcesWhereSnippet extends Snippet
{
    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties(): array
    {
        return [
            'fields::associativeJson' => '',
            'where::associativeJson' => '',
            'emptyRedirect' => '',
            'toPlaceholder' => ''
        ];
    }

    /**
     * Execute the snippet and return the result.
     *
     * @return string
     * @throws /Exception
     */
    public function execute(): string
    {
        $where = $this->getProperty('where');
        if ($where == false) {
            $where = array();
        }
        $emptyRedirect = $this->getProperty('emptyRedirect');
        $toPlaceholder = $this->getProperty('toPlaceholder');
        $fields = $this->getProperty('fields');

        // URL parameter
        foreach ($fields as $key => $field) {
            $field = explode('::', $field);
            $value = $this->getProperty($key, $this->modx->getOption($key, $_REQUEST, false));
            $phValue = $this->modx->stripTags($value);
            $operator = $field[1] ?? ':=';
            $junction = $field[2] ?? '';

            $subfields = explode(',', $field[0]);
            $subwhere = [];
            $subjunction = $junction;
            foreach ($subfields as $subfield) {
                $this->setWhere($subwhere, $key, $subfield, $value, $phValue, $operator, $subjunction);
                $subjunction = 'OR:';
            }
            if (!empty($subwhere)) {
                $where[] = $subwhere;
            }
        }

        if (empty($where) && $emptyRedirect) {
            $this->modx->sendRedirect($this->modx->makeUrl($emptyRedirect));
        }

        $output = str_replace(array('[[', ']]'), array('[ [', '] ]'), json_encode($where));

        if ($toPlaceholder) {
            $this->modx->setPlaceholder($toPlaceholder, $output);
            $output = '';
        }
        return $output;
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
    private function setWhere(&$where, $option, $field, $value, $phValue = null, $operator = '=', $junction = '')
    {
        $phValue = is_null($phValue) ? $value : $phValue;
        if ($value) {
            if (is_array($value)) {
                if ($operator == '=' || $operator == 'IN') {
                    $where[$junction . $field . ':IN'] = $value;
                    $this->modx->setPlaceholder($option . '_value', json_encode($phValue));
                } else {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Operator can\'t be different than `=` or `IN` with an array value');
                }
            } else {
                $where[$junction . $field . ':' . $operator] = ($operator == 'LIKE') ? '%' . $value . '%' : $value;
                $this->modx->setPlaceholder($option . '_value', $phValue);
            }
        }
    }
}
