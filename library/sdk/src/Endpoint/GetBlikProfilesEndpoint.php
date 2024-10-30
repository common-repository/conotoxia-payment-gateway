<?php

namespace CKPL\Pay\Endpoint;

use CKPL\Pay\Client\RawOutput\RawOutputInterface;
use CKPL\Pay\Configuration\ConfigurationInterface;
use CKPL\Pay\Endpoint\ConfigurationFactory\EndpointConfigurationFactoryInterface;
use CKPL\Pay\Exception\Endpoint\GetBlikEndpointException;
use CKPL\Pay\Exception\PayloadException;
use CKPL\Pay\Model\Collection\BlikProfileResponseModelCollection;
use CKPL\Pay\Model\ProcessedInputInterface;
use CKPL\Pay\Model\ProcessedOutputInterface;
use CKPL\Pay\Model\Request\GetBlikProfileRequestModel;
use CKPL\Pay\Model\Response\BlikAliasResponseModel;

class GetBlikProfilesEndpoint implements EndpointInterface
{
    /**
     * @type string
     */
    const ALIASES = 'aliases';

    /**
     * @type string
     */
    const CUSTOMER_ID = 'customerId';

    /**
     * @type string
     */
    const RESPONSE_ALIAS_NAME = 'aliasName';

    /**
     * @type string
     */
    const RESPONSE_ALIAS_KEY = 'aliasKey';

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;
    private $pointOfSaleId;


    /**
     * GetBlikProfilesEndpoint.php constructor.
     *
     * @param ConfigurationInterface $configuration
     * @param string $pointOfSaleId
     */
    public function __construct(ConfigurationInterface $configuration, string $pointOfSaleId)
    {
        $this->configuration = $configuration;
        $this->pointOfSaleId = $pointOfSaleId;
    }

    /**
     * @param EndpointConfigurationFactoryInterface $configurationFactory
     *
     * @return void
     */
    public function configuration(EndpointConfigurationFactoryInterface $configurationFactory): void
    {
        $configurationFactory
            ->url($this->getUrl())
            ->asPost()
            ->toPayments()
            ->encodeWithJson()
            ->signRequest()
            ->expectSignedResponse()
            ->authorized();
    }

    /**
     * @param array $parameters
     *
     * @return ProcessedInputInterface|null
     * @throws GetBlikEndpointException
     */
    public function processRawInput(array $parameters): ?ProcessedInputInterface
    {
        if (!isset($parameters[static::CUSTOMER_ID])) {
            throw new GetBlikEndpointException(
                sprintf(GetBlikEndpointException::MISSING_REQUEST_PARAMETERS,
                    static::CUSTOMER_ID));
        }

        $model = new GetBlikProfileRequestModel();
        $model->setCustomerId($parameters[static::CUSTOMER_ID]);

        return $model;
    }

    /**
     * @param RawOutputInterface $rawOutput
     *
     * @return ProcessedOutputInterface
     * @throws GetBlikEndpointException
     */
    public function processRawOutput(RawOutputInterface $rawOutput): ProcessedOutputInterface
    {
        $payload = $rawOutput->getPayload();
        
        try {
            $blikProfileCollection = new BlikProfileResponseModelCollection();

            foreach (($payload->expectArrayOrNull(static::ALIASES) ?? []) as $alias) {
                if (!isset($alias[static::RESPONSE_ALIAS_NAME])) {
                    throw new GetBlikEndpointException(
                        sprintf(GetBlikEndpointException::MISSING_RESPONSE_PARAMETER,
                            static::RESPONSE_ALIAS_NAME
                        )
                    );
                }

                if (!isset($alias[static::RESPONSE_ALIAS_KEY])) {
                    throw new GetBlikEndpointException(
                        sprintf(GetBlikEndpointException::MISSING_RESPONSE_PARAMETER,
                            static::RESPONSE_ALIAS_KEY
                        )
                    );
                }

                $blikProfileCollection->add(
                    (new BlikAliasResponseModel())
                        ->setAliasName($alias[static::RESPONSE_ALIAS_NAME])
                        ->setAliasKey($alias[static::RESPONSE_ALIAS_KEY])
                );
            }
            return $blikProfileCollection;
        } catch (PayloadException $e) {
            throw new GetBlikEndpointException('Unable to get blik profile', 0, $e);
        }
    }

    /**
     * @return string
     */
    private function getUrl(): string
    {
        return 'profiles/' . $this->pointOfSaleId . '/blik';
    }
}