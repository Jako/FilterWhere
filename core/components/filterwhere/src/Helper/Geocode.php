<?php
/**
 * Geocode classfile
 *
 * @package filterwhere
 * @subpackage classfile
 */

namespace TreehillStudio\FilterWhere\Helper;

use FilterWhere;
use Geocoder\Collection;
use Geocoder\Exception\Exception;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use GuzzleHttp\Client;
use modX;

/**
 * Class Geocode
 */
class Geocode
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * A reference to the FilterWhere instance
     * @var FilterWhere $filterwhere
     */
    public $filterwhere;

    /**
     * Geocode constructor
     *
     * @param modX $modx A reference to the modX instance.
     */
    public function __construct(modX &$modx)
    {
        $this->modx =& $modx;
        $this->filterwhere =& $modx->filterwhere;
    }

    /**
     * @param $address
     * @return Collection
     * @throws Exception
     */
    public function geocode($address)
    {
        $httpClient = new Client();
        $provider = new GoogleMaps($httpClient, $this->filterwhere->getOption('google_maps_region'), $this->filterwhere->getOption('google_maps_api_key'));
        $geocoder = new StatefulGeocoder($provider, $this->modx->getOption('locale', [], 'en'));

        return $geocoder->geocodeQuery(GeocodeQuery::create($address));
    }
}
