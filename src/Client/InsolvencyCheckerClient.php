<?php declare(strict_types=1);

namespace AsisTeam\ISIR\Client;

use AsisTeam\ISIR\Client\Request\Options;
use AsisTeam\ISIR\Client\Response\Hydrator;
use AsisTeam\ISIR\Entity\Insolvency;
use AsisTeam\ISIR\Enum\Relevancy;
use AsisTeam\ISIR\Exception\Runtime\NoRecordFoundException;
use AsisTeam\ISIR\Exception\Runtime\RequestException;
use DateTimeImmutable;
use SoapClient;
use SoapFault;

final readonly class InsolvencyCheckerClient
{

    public function __construct(private SoapClient $client, private ?Options $options = null)
    {
    }

    /**
     * @return Insolvency[]
     */
    public function checkPersonById(string $personId, bool $activeOnly = false): array
    {
        $opts = (new Options())->setMaxResultRelevancy(Relevancy::BY_PERSONAL_ID);

        return $this->check(['rc' => $personId], $activeOnly, $opts);
    }

    /**
     * @return Insolvency[]
     */
    public function checkCompanyById(string $companyId, bool $activeOnly = false): array
    {
        $opts = (new Options())->setMaxResultRelevancy(Relevancy::BY_COMPANY_ID);

        return $this->check(['ic' => $companyId], $activeOnly, $opts);
    }

    /**
     * @return Insolvency[]
     */
    public function checkProceeding(int $no, int $vintage, bool $activeOnly = false): array
    {
        $opts = (new Options())->setMaxResultRelevancy(Relevancy::BY_FILE_NUMBER);

        return $this->check(['bcVec' => $no, 'rocnik' => $vintage], $activeOnly, $opts);
    }

    /**
     * @return Insolvency[]
     */
    public function checkCompanyByName(
        string $name,
        bool $activeOnly = false,
        ?Options $opts = null
    ): array {
        return $this->check(['nazevOsoby' => $name], $activeOnly, $opts);
    }

    /**
     * @return Insolvency[]
     */
    public function checkPersonByName(
        string $firstname,
        string $lastname,
        bool $activeOnly = false,
        ?Options $opts = null
    ): array {
        return $this->check(['nazevOsoby' => $lastname, 'jmeno' => $firstname], $activeOnly, $opts);
    }

    /**
     * @return Insolvency[]
     */
    public function checkPersonByNameAndBirth(
        string $lastname,
        DateTimeImmutable $birthday,
        bool $activeOnly = false,
        ?Options $opts = null
    ): array {
        return $this->check(
            [
                'nazevOsoby' => $lastname,
                'datumNarozeni' => $birthday->format('Y-m-d'),
            ],
            $activeOnly,
            $opts
        );
    }

    /**
     * @param mixed[] $params
     * @return Insolvency[]
     */
    private function check(array $params, bool $activeOnly = false, ?Options $reqOpts = null): array
    {
        try {
            $opts = $this->getOpts($reqOpts);
            $data = array_merge($params, $opts);
            $data['filtrAktualniRizeni'] = Options::boolToStr($activeOnly);

            $resp = $this->client->getIsirWsCuzkData($data);

            return Hydrator::hydrate($resp);
        } catch (SoapFault $e) {
            throw new RequestException(
                sprintf('SOAP error %s. Request: %s', $e->getMessage(), $this->client->__getLastRequest()),
                0,
                $e
            );
        } catch (NoRecordFoundException) {
            return [];
        }
    }

    /**
     * @return mixed[]
     */
    private function getOpts(?Options $reqOpts = null): array
    {
        $opts = [];

        if ($this->options !== null) {
            $opts = $this->options->toArray();
        }

        if ($reqOpts !== null) {
            $opts = $reqOpts->toArray();
        }

        return $opts;
    }

}
