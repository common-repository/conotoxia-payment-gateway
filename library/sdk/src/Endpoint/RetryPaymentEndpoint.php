<?php

declare(strict_types=1);

namespace CKPL\Pay\Endpoint;

use CKPL\Pay\Client\RawOutput\RawOutputInterface;
use CKPL\Pay\Configuration\ConfigurationInterface;
use CKPL\Pay\Endpoint\ConfigurationFactory\EndpointConfigurationFactoryInterface;
use CKPL\Pay\Exception\Endpoint\RetryPaymentEndpointException;
use CKPL\Pay\Exception\EndpointException;
use CKPL\Pay\Exception\PayloadException;
use CKPL\Pay\Model\ProcessedInputInterface;
use CKPL\Pay\Model\ProcessedOutputInterface;
use CKPL\Pay\Model\Request\RetryPaymentRequestModel;
use CKPL\Pay\Model\Response\RetriedPaymentResponseModel;

class RetryPaymentEndpoint implements EndpointInterface
{
    /**
     * @type string
     */
    protected const ENDPOINT = 'payments/retry';

    /**
     * @type string
     */
    const PARAMETER_PAYMENT_ID = 'paymentId';

    /**
     * @type string
     */
    const PARAMETER_APPROVE_URL = 'approveUrl';

    /**
     * @type string
     */
    const PARAMETER_TOKEN = 'token';

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param EndpointConfigurationFactoryInterface $configurationFactory
     * @return void
     */
    public function configuration(EndpointConfigurationFactoryInterface $configurationFactory): void
    {
        $configurationFactory
            ->url(static::ENDPOINT)
            ->asPost()
            ->toPayments()
            ->encodeWithJson()
            ->signRequest()
            ->expectSignedResponse()
            ->authorized();
    }

    /**
     * @param array $parameters
     * @return ProcessedInputInterface|null
     * @throws RetryPaymentEndpointException
     */
    public function processRawInput(array $parameters): ?ProcessedInputInterface
    {
        if (!isset($parameters[self::PARAMETER_PAYMENT_ID])) {
            throw new RetryPaymentEndpointException(
                sprintf(EndpointException::MISSING_REQUEST_PARAMETERS, self::PARAMETER_PAYMENT_ID)
            );
        }

        $model = new RetryPaymentRequestModel();
        $model->setPaymentId($parameters[self::PARAMETER_PAYMENT_ID]);

        return $model;
    }

    /**
     * @param RawOutputInterface $rawOutput
     * @return ProcessedOutputInterface
     * @throws RetryPaymentEndpointException
     * @throws PayloadException
     */
    public function processRawOutput(RawOutputInterface $rawOutput): ProcessedOutputInterface
    {
        $paymentRetry = $rawOutput->getPayload();

        if (!$paymentRetry->hasElement(static::PARAMETER_PAYMENT_ID)) {
            throw new RetryPaymentEndpointException(
                sprintf(EndpointException::MISSING_RESPONSE_PARAMETER, static::PARAMETER_PAYMENT_ID)
            );
        }

        if (!$paymentRetry->hasElement(static::PARAMETER_APPROVE_URL)) {
            throw new RetryPaymentEndpointException(
                sprintf(EndpointException::MISSING_RESPONSE_PARAMETER, static::PARAMETER_APPROVE_URL)
            );
        }

        if (!$paymentRetry->hasElement(static::PARAMETER_TOKEN)) {
            throw new RetryPaymentEndpointException(
                sprintf(EndpointException::MISSING_RESPONSE_PARAMETER, static::PARAMETER_TOKEN)
            );
        }

        $model = new RetriedPaymentResponseModel();
        $model->setPaymentId($paymentRetry->expectStringOrNull(static::PARAMETER_PAYMENT_ID));
        $model->setApproveUrl($paymentRetry->expectStringOrNull(static::PARAMETER_APPROVE_URL));
        $model->setToken($paymentRetry->expectStringOrNull(static::PARAMETER_TOKEN));

        return $model;
    }
}
