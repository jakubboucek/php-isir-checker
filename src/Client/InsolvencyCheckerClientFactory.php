<?php declare(strict_types=1);

namespace AsisTeam\ISIR\Client;

use AsisTeam\ISIR\Client\Request\Options;
use SoapClient;

final readonly class InsolvencyCheckerClientFactory
{

    private const WSDL = __DIR__ . '/wsdl/IsirWsCuzkService.wsdl';

    public function __construct(private ?Options $opts = null)
    {
    }

    public function create(): InsolvencyCheckerClient
    {
        $soap = new SoapClient(
            self::WSDL,
            [
                'encoding' => 'UTF-8',
                'trace' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]
        );

        return new InsolvencyCheckerClient($soap, $this->opts);
    }

}
