<?php

declare(strict_types=1);

namespace CKPL\Pay\Payment;

use CKPL\Pay\Client\ClientInterface;
use CKPL\Pay\Definition\BlikProfile\BlikProfileInterface;
use CKPL\Pay\Definition\BlikProfile\Builder\BlikProfileBuilder;
use CKPL\Pay\Definition\BlikProfile\Builder\BlikProfileBuilderInterface;
use CKPL\Pay\Definition\Confirm\Builder\ConfirmPaymentBuilder;
use CKPL\Pay\Definition\Confirm\Builder\ConfirmPaymentBuilderInterface;
use CKPL\Pay\Definition\Confirm\ConfirmPaymentInterface;
use CKPL\Pay\Definition\Payment\Builder\PaymentBuilder;
use CKPL\Pay\Definition\Payment\Builder\PaymentBuilderInterface;
use CKPL\Pay\Definition\Payment\PaymentInterface;
use CKPL\Pay\Definition\Retry\RetryPaymentInterface;
use CKPL\Pay\Endpoint\ConfirmPaymentEndpoint;
use CKPL\Pay\Endpoint\EndpointInterface;
use CKPL\Pay\Endpoint\GetAvailablePaymentMethodsEndpoint;
use CKPL\Pay\Endpoint\GetBlikProfilesEndpoint;
use CKPL\Pay\Endpoint\GetPaymentsEndpoint;
use CKPL\Pay\Endpoint\GetPaymentStatusEndpoint;
use CKPL\Pay\Endpoint\MakePaymentEndpoint;
use CKPL\Pay\Endpoint\RetryPaymentEndpoint;
use CKPL\Pay\Exception\ClientException;
use CKPL\Pay\Exception\DecodedReturnException;
use CKPL\Pay\Exception\Exception;
use CKPL\Pay\Model\Collection\AvailablePaymentMethodsResponseModelCollection;
use CKPL\Pay\Model\Collection\BlikProfileResponseModelCollection;
use CKPL\Pay\Model\Collection\PaymentResponseModelCollection;
use CKPL\Pay\Model\Response\ConfirmPaymentResponseModel;
use CKPL\Pay\Model\Response\CreatedPaymentResponseModel;
use CKPL\Pay\Model\Response\PaymentStatusResponseModel;
use CKPL\Pay\Model\Response\RetriedPaymentResponseModel;
use CKPL\Pay\Payment\DecodedReturn\DecodedReturnInterface;
use CKPL\Pay\Payment\ReturnDecoder\ReturnDecoder;
use CKPL\Pay\Service\BaseService;


/**
 * Class PaymentManager.
 *
 * Payments related features such as
 * ability to create payment, check payment status,
 * decode return response, get list of all payments related to client in service.
 *
 * @package CKPL\Pay\Payment
 */
class PaymentManager extends BaseService implements PaymentManagerInterface
{
    /**
     * Creates payments builder that can help with generating Payment definition.
     *
     * @return PaymentBuilderInterface
     */
    public function createPaymentBuilder(): PaymentBuilderInterface
    {
        return new PaymentBuilder();
    }

    /**
     * Gets all payments related to client from Payment Service.
     *
     * Entries can be filtered using following parameters:
     * * `payments_ids` - IDs of payments that will be fetched from Payment Service.
     * * `external_payment_id` - External (app) payment ID. Method will return only payments with specified external ID.
     * * `creation_date_from` - creation time in Zulu format. Method will return only payments created after
     *                          specified date.
     * * `creation_date_to` - creation time in Zulu format. Method will return only payments created before
     *                          specified date.
     * * `booked_date_from` - time, in Zulu format, when payment was booked. Method will return only payments booked
     *                        after specified date.
     * * `booked_date_to` - time, in Zulu format, when payment was booked. Method will return only payments booked
     *                      before specified date.
     * * `page_number` - page number.
     * * `page_size` - number of payments per page.
     *
     * @param array $parameters filter parameters
     *
     * @return PaymentResponseModelCollection
     * @throws Exception       library-level related problem e.g. invalid data model.
     *
     * @throws ClientException request-level related problem e.g. HTTP errors, API errors.
     */
    public function getPayments(array $parameters = []): PaymentResponseModelCollection
    {
        $client = $this->getClient(new GetPaymentsEndpoint());

        $client->request()->parameters($parameters)->send();

        $paymentCollection = $client->getResponse()->getProcessedOutput();

        if ($paymentCollection instanceof PaymentResponseModelCollection) {
            return $paymentCollection;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }

    /**
     * Confirm payment
     *
     * @param ConfirmPaymentInterface $confirmPayment
     * @return ConfirmPaymentResponseModel
     * @throws Exception
     */
    public function confirmPayment(ConfirmPaymentInterface $confirmPayment): ConfirmPaymentResponseModel
    {
        $client = $this->getClient(new ConfirmPaymentEndpoint($this->configuration, $confirmPayment->getToken()));

        $client
            ->request()
            ->parameters($this->confirmPaymentToParameters($confirmPayment))
            ->headers($this->confirmPaymentToHeaders($confirmPayment))->send();

        $paymentConfirmResponse = $client->getResponse()->getProcessedOutput();

        if ($paymentConfirmResponse instanceof ConfirmPaymentResponseModel) {
            return $paymentConfirmResponse;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }

    /**
     * @param BlikProfileInterface $blikProfile
     * @return BlikProfileResponseModelCollection
     * @throws ClientException
     * @throws Exception
     */
    public function getBlikProfiles(BlikProfileInterface $blikProfile): BlikProfileResponseModelCollection
    {
        $client = $this->getClient(new GetBlikProfilesEndpoint($this->configuration, $blikProfile->getPointOfSaleId()));

        $client
            ->request()
            ->parameters($this->getBlikProfileToParameters($blikProfile))->send();

        $blikProfileResponse = $client->getResponse()->getProcessedOutput();

        if ($blikProfileResponse instanceof BlikProfileResponseModelCollection) {
            return $blikProfileResponse;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }


    /**
     * Gets available payment methods with given Point of Sale identifier and Currency.
     * @param string $pointOfSaleId
     * @param string $currency
     * @return AvailablePaymentMethodsResponseModelCollection
     * @throws ClientException
     * @throws Exception
     */
    public function getAvailablePaymentMethods(string $pointOfSaleId, string $currency): AvailablePaymentMethodsResponseModelCollection
    {
        $client = $this->getClient(new GetAvailablePaymentMethodsEndpoint());

        $parameters = [
            'currency' => $currency,
            'pointOfSaleId' => $pointOfSaleId
        ];

        $client->request()->parameters($parameters)->send();

        $paymentStatusResponseModel = $client->getResponse()->getProcessedOutput();

        if ($paymentStatusResponseModel instanceof AvailablePaymentMethodsResponseModelCollection) {
            return $paymentStatusResponseModel;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }

    /**
     * Creates payment in Payment Service from definition and returns
     * payment ID and URL given by service.
     *
     * Received URL must be forwarded to user to be able to proceed with payment.
     *
     * @param PaymentInterface $payment payment definition
     *
     * @return CreatedPaymentResponseModel
     * @throws Exception       library-level related problem e.g. invalid data model.
     *
     * @throws ClientException request-level related problem e.g. HTTP errors, API errors.
     */
    public function makePayment(PaymentInterface $payment): CreatedPaymentResponseModel
    {
        $client = $this->getClient(new MakePaymentEndpoint($this->configuration));

        $client->request()->parameters($this->paymentToParameters($payment))->headers($this->paymentHeaders($payment))->send();

        $paymentModel = $client->getResponse()->getProcessedOutput();

        if ($paymentModel instanceof CreatedPaymentResponseModel) {
            return $paymentModel;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }

    /**
     * @param RetryPaymentInterface $retryPayment
     * @return RetriedPaymentResponseModel
     * @throws ClientException request-level related problem e.g. HTTP errors, API errors.
     * @throws Exception library-level related problem e.g. invalid data model.
     */
    public function retryPayment(RetryPaymentInterface $retryPayment): RetriedPaymentResponseModel
    {
        $client = $this->getClient(new RetryPaymentEndpoint($this->configuration));

        $client->request()->parameters($this->retryPaymentToParameters($retryPayment))->send();

        $retryPaymentModel = $client->getResponse()->getProcessedOutput();

        if ($retryPaymentModel instanceof RetriedPaymentResponseModel) {
            return $retryPaymentModel;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }

    /**
     * Decodes return / error URL data.
     *
     * Example:
     *     $this->decodeReturn($_GET['data']);
     *
     * @param string $return
     *
     * @return DecodedReturnInterface
     * @throws DecodedReturnException decode-level related problem e.g. missing parameter in response.
     *
     */
    public function decodeReturn(string $return): DecodedReturnInterface
    {
        $returnDecoder = new ReturnDecoder($this->dependencyFactory->getSecurityManager());

        return $returnDecoder->decode($return);
    }

    /**
     * @throws ClientException
     * @throws Exception
     */
    public function getPaymentStatus(string $paymentId): PaymentStatusResponseModel
    {
        $client = $this->getClient(new GetPaymentStatusEndpoint($this->configuration));

        $parameters = [GetPaymentStatusEndpoint::REQUEST_PAYMENT_ID => $paymentId];

        $client->request()->parameters($parameters)->send();

        $paymentStatusResponseModel = $client->getResponse()->getProcessedOutput();

        if ($paymentStatusResponseModel instanceof PaymentStatusResponseModel) {
            return $paymentStatusResponseModel;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }

    /**
     * Creates payment confirm builder that can help with generating payment confirm definition.
     *
     * @return ConfirmPaymentBuilderInterface
     */
    public function createConfirmPaymentBuilder(): ConfirmPaymentBuilderInterface
    {
        return new ConfirmPaymentBuilder();
    }

    /**
     * Creates Blik profile builder that can help with generating Blik profile definition.
     *
     * @return BlikProfileBuilderInterface
     */
    public function createBlikProfileBuilder(): BlikProfileBuilderInterface
    {
        return new BlikProfileBuilder();
    }

    /**
     * @param PaymentInterface $payment
     *
     * @return array
     */
    protected function paymentToParameters(PaymentInterface $payment): array
    {
        return [
            'currency' => ($payment->getAmount() ? $payment->getAmount()->getCurrency() : null),
            'value' => ($payment->getAmount() ? $payment->getAmount()->getValue() : null),
            'internal_payment_id' => $payment->getExternalPaymentId(),
            'point_of_sale' => $this->configuration->getPointOfSale(),
            'category' => $this->configuration->getCategory(),
            'description' => $payment->getDescription(),
            'allow_pay_later' => $payment->getAllowPayLater(),
            'notification_url' => $payment->getNotificationUrl(),
            'return_url' => $payment->getReturnUrl(),
            'error_url' => $payment->getErrorUrl(),
            'integrationPlatform' => $payment->getIntegrationPlatform(),
            'notificationUrlParameters' => $payment->getNotificationUrlParameters(),
            'store_customer' => $payment->getStoreCustomer(),
            'selected_payment_method' => $payment->getSelectedPaymentMethod(),
            'preferred_user_locale' => $payment->getPreferredUserLocale(),
            'retry_enabled' => $payment->getRetryEnabled(),
        ];
    }

    protected function paymentHeaders(PaymentInterface $payment): array
    {
        $headers = [];
        if ($payment->getAcceptLanguage()) {
            $headers[] = ['Accept-Language', $payment->getAcceptLanguage()];
        }
        if ($payment->getUserAcceptLanguage()) {
            $headers[] = ['User-Accept-Language', $payment->getUserAcceptLanguage()];
        }
        return $headers;
    }

    /**
     * @param RetryPaymentInterface $retryPayment
     * @return array
     */
    protected function retryPaymentToParameters(RetryPaymentInterface $retryPayment): array
    {
        return ['paymentId' => $retryPayment->getPaymentId()];
    }

    /**
     * @param EndpointInterface $endpoint
     * @return ClientInterface
     */
    private function getClient(EndpointInterface $endpoint): ClientInterface
    {
        return $this->dependencyFactory->createClient($endpoint,
            $this->configuration,
            $this->dependencyFactory->getSecurityManager(),
            $this->dependencyFactory->getAuthenticationManager());
    }

    /**
     * @param ConfirmPaymentInterface $confirmPayment
     * @return array
     */
    private function confirmPaymentToParameters(ConfirmPaymentInterface $confirmPayment): array
    {
        $additionalData = (object)array(
            'email' => $confirmPayment->getEmail(),
            'firstName' => $confirmPayment->getFirstName(),
            'lastName' => $confirmPayment->getLastName()
        );

        if ($confirmPayment->getBlikCode() === null) {
            return [
                'type' => $confirmPayment->getType(),
                'aliasName' => $confirmPayment->getAliasName(),
                'aliasKey' => $confirmPayment->getAliasKey(),
                'customerId' => $confirmPayment->getCustomerId(),
                'additionalData' => $additionalData,
                'notificationsLocale' => $confirmPayment->getNotificationsLocale()
            ];
        }
        if ($confirmPayment->getCustomerId() === null) {
            return [
                'type' => $confirmPayment->getType(),
                'blikCode' => $confirmPayment->getBlikCode(),
                'additionalData' => $additionalData,
                'notificationsLocale' => $confirmPayment->getNotificationsLocale()
            ];
        }
        return [
            'type' => $confirmPayment->getType(),
            'blikCode' => $confirmPayment->getBlikCode(),
            'customerId' => $confirmPayment->getCustomerId(),
            'additionalData' => $additionalData,
            'notificationsLocale' => $confirmPayment->getNotificationsLocale()
        ];
    }

    private function getBlikProfileToParameters(BlikProfileInterface $blikProfile): array
    {
        return [
            'customerId' => $blikProfile->getCustomerId()
        ];
    }

    /**
     * @param ConfirmPaymentInterface $confirmPayment
     * @return array|array[]
     */
    private function confirmPaymentToHeaders(ConfirmPaymentInterface $confirmPayment): array
    {
        return array_filter(
            [
                $this->mapToArray('Accept-Language', $confirmPayment->getAcceptLanguage()),
                $this->mapToArray('User-Screen-Resolution', $confirmPayment->getUserScreenResolution()),
                $this->mapToArray('User-Real-Port', $confirmPayment->getUserPort()),
                $this->mapToArray('User-Real-Ip', $confirmPayment->getUserIpAddress()),
                $this->mapToArray('User-Agent', $confirmPayment->getUserAgent()),
                $this->mapToArray('fingerprint', $confirmPayment->getFingerprint()),
            ]
        );
    }

    /**
     * @param string $headerName
     * @param string|null $headerValue
     * @return array
     */
    public function mapToArray(string $headerName, ?string $headerValue): array
    {
        if ($headerValue) {
            return [$headerName, $headerValue];
        }
        return [];
    }
}
