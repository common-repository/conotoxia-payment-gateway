<?php

namespace CKPL\Pay\Endpoint;

use CKPL\Pay\Client\RawOutput\RawOutputInterface;
use CKPL\Pay\Configuration\ConfigurationInterface;
use CKPL\Pay\Endpoint\ConfigurationFactory\EndpointConfigurationFactoryInterface;
use CKPL\Pay\Exception\Endpoint\GetAvailablePaymentMethodsException;
use CKPL\Pay\Exception\PayloadException;
use CKPL\Pay\Model\Collection\AvailablePaymentMethodsResponseModelCollection;
use CKPL\Pay\Model\ProcessedInputInterface;
use CKPL\Pay\Model\ProcessedOutputInterface;
use CKPL\Pay\Model\Request\GetAvailablePaymentMethodsRequestModel;
use CKPL\Pay\Model\Response\IssuerResponseModel;
use CKPL\Pay\Model\Response\PaymentMethodResponseModel;

class GetAvailablePaymentMethodsEndpoint implements EndpointInterface
{
    /**
     * @type string
     */
    protected const ENDPOINT = 'payments/methods';

    /**
     * @type string
     */
    const PARAMETER_CURRENCY = 'currency';

    /**
     * @type string
     */
    const PARAMETER_POINT_OF_SALE_ID = 'pointOfSaleId';

    /**
     * @type string
     */
     const RESPONSE_DATA = 'data';

    /**
     * @type string
     */
     const RESPONSE_TYPE = 'type';

    /**
     * @type string
     */
     const RESPONSE_STATUS = 'status';

    /**
     * @type string
     */
     const RESPONSE_ISSUERS = 'issuers';

    /**
     * @type string
     */
     const RESPONSE_ISSUER_ID = 'id';

    /**
     * @type string
     */
     const RESPONSE_ISSUER_NAME = 'name';

    /**
     * @type string
     */
     const RESPONSE_ISSUER_CODE = 'code';

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @param EndpointConfigurationFactoryInterface $configurationFactory
     *
     * @return void
     */
    public function configuration(EndpointConfigurationFactoryInterface $configurationFactory): void
    {
        $configurationFactory
            ->url(static::ENDPOINT)
            ->asGet()
            ->toPayments()
            ->expectSignedResponse()
            ->authorized();
    }

    /**
     * @param array $parameters
     *
     * @return ProcessedInputInterface|null
     * @throws GetAvailablePaymentMethodsException
     */
    public function processRawInput(array $parameters): ?ProcessedInputInterface
    {
        $result = new GetAvailablePaymentMethodsRequestModel();

        if (isset($parameters[static::PARAMETER_CURRENCY])) {
            $result->setCurrency($parameters[static::PARAMETER_CURRENCY]);
        } else {
            throw new GetAvailablePaymentMethodsException('Currency is required.');
        }

        if (isset($parameters[static::PARAMETER_POINT_OF_SALE_ID])) {
            $result->setPointOfSaleId($parameters[static::PARAMETER_POINT_OF_SALE_ID]);
        } else {
            throw new GetAvailablePaymentMethodsException('Point of sale identifier is required.');
        }

        return $result;
    }


    /**
     * @param RawOutputInterface $rawOutput
     *
     * @return ProcessedOutputInterface
     * @throws GetAvailablePaymentMethodsException
     *
     */
    public function processRawOutput(RawOutputInterface $rawOutput): ProcessedOutputInterface
    {
        try {
            $payload = $rawOutput->getPayload();
            $data = $payload->expectArrayOrNull(static::RESPONSE_DATA);

            $paymentMethodsCollection = new AvailablePaymentMethodsResponseModelCollection();
            foreach($data as $paymentMethod) {
                if (!isset($paymentMethod[static::RESPONSE_TYPE])) {
                    throw new GetAvailablePaymentMethodsException(
                        sprintf(GetAvailablePaymentMethodsException::MISSING_RESPONSE_PARAMETER,
                            static::RESPONSE_TYPE
                        )
                    );
                }

                if (!isset($paymentMethod[static::RESPONSE_STATUS])) {
                    throw new GetAvailablePaymentMethodsException(
                        sprintf(GetAvailablePaymentMethodsException::MISSING_RESPONSE_PARAMETER,
                            static::RESPONSE_STATUS
                        )
                    );
                }

                $paymentMethodModel = (new PaymentMethodResponseModel())
                    ->setType($paymentMethod[static::RESPONSE_TYPE])
                    ->setStatus($paymentMethod[static::RESPONSE_STATUS]
                );

                if (isset($paymentMethod[static::RESPONSE_ISSUERS])) {
                    foreach ($paymentMethod[static::RESPONSE_ISSUERS] as $issuer) {
                        $issuerResponseModel = new IssuerResponseModel();
                        if (isset($issuer[self::RESPONSE_ISSUER_ID])) {
                            $issuerResponseModel->setId($issuer[self::RESPONSE_ISSUER_ID]);
                        }
                        if (isset($issuer[self::RESPONSE_ISSUER_NAME])) {
                            $issuerResponseModel->setName($issuer[self::RESPONSE_ISSUER_NAME]);
                        }
                        if (isset($issuer[self::RESPONSE_ISSUER_CODE])) {
                            $issuerResponseModel->setCode($issuer[self::RESPONSE_ISSUER_CODE]);
                        }
                        $paymentMethodModel->addIssuer($issuerResponseModel);
                    }
                }
                $paymentMethodsCollection->add($paymentMethodModel);
            }
            return $paymentMethodsCollection;
        } catch (PayloadException $e) {
            throw new GetAvailablePaymentMethodsException('Unable to get payment status.', 0, $e);
        }
    }
}

