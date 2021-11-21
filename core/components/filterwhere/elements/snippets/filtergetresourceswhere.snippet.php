<?php
/**
 * FilterGetResourcesWhere Snippet
 *
 * @package filterwhere
 * @subpackage snippet
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

use TreehillStudio\FilterWhere\Snippets\FilterGetResourcesWhereSnippet;

$corePath = $modx->getOption('filterwhere.core_path', null, $modx->getOption('core_path') . 'components/filterwhere/');
/** @var FilterWhere $filterwhere */
$filterwhere = $modx->getService('filterwhere', 'FilterWhere', $corePath . 'model/filterwhere/', [
    'core_path' => $corePath
]);

$snippet = new FilterGetResourcesWhereSnippet($modx, $scriptProperties);
if ($snippet instanceof TreehillStudio\FilterWhere\Snippets\FilterGetResourcesWhereSnippet) {
    return $snippet->execute();
}
return 'TreehillStudio\FilterWhere\Snippets\FilterGetResourcesWhereSnippet class not found';