<?php
/**
 * FilterGetResourcesSortbySnippet Snippet
 *
 * @package filterwhere
 * @subpackage snippet
 */

namespace TreehillStudio\FilterWhere\Snippets;

class FilterGetResourcesSortbySnippet extends Snippet
{
    /** @var string $sortby */
    public $sortby;

    /** @var string $sortdir */
    public $sortdir;

    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'sortkey' => 'sortby',
            'dirkey' => 'sortdir',
            'sortby::associativeJson' => '[]',
            'toPlaceholder' => '',
            'varName::allowedVarName' => 'REQUEST',
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
        $toPlaceholder = $this->getProperty('toPlaceholder');

        switch ($this->getProperty('varName')) {
            case 'GET':
            default:
                $sortby = $_GET[$this->getProperty('sortkey')];
                $sortdir = $_GET[$this->getProperty('dirkey')];
                break;
            case 'POST':
                $sortby = $_POST[$this->getProperty('sortkey')];
                $sortdir = $_POST[$this->getProperty('dirkey')];
                $this->values = $_POST;
                break;
            case 'REQUEST':
                $sortby = $_REQUEST[$this->getProperty('sortkey')];
                $sortdir = $_REQUEST[$this->getProperty('dirkey')];
        }

        // URL parameter
        $sortby = preg_replace('/[^a-zA-Z0-9_.]/', '', $sortby);
        $this->modx->setPlaceholder($this->getProperty('sortkey') . '_value', $sortby);
        $sortdir = in_array(strtoupper($sortdir), ['ASC', 'DESC']) ? strtoupper($sortdir) : 'ASC';
        $this->modx->setPlaceholder($this->getProperty('dirkey') . '_value', $sortdir);

        $output = $this->getProperty('sortby');
        if ($sortby && $sortdir) {
            $output[$sortby] = $sortdir;
            $output = str_replace(['{"', '"}'], ['{ "', '" }'], json_encode($output));
        } else {
            $output = '';
        }

        if ($toPlaceholder) {
            $this->modx->setPlaceholder($toPlaceholder, $output);
            $output = '';
        }
        return $output;
    }

    /**
     * @param $value
     * @return string
     */
    public function getAllowedVarName($value)
    {
        if (in_array(strtoupper($value), ['REQUEST', 'GET', 'POST'])) {
            return strtoupper($value);
        } else {
            return 'REQUEST';
        }
    }
}
