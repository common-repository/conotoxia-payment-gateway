<?php

declare(strict_types=1);

namespace CKPL\Pay\Merchant;

use CKPL\Pay\Exception\ClientException;
use CKPL\Pay\Exception\Exception;
use CKPL\Pay\Exception\StorageException;
use CKPL\Pay\Model\Response\AddedKeyResponseModel;

/**
 * Interface MerchantManagerInterface.
 *
 * Merchant related functionality such as
 * ability to check if public key is synchronized with
 * service, send public key to service, get ID for public key
 * if already sent to service, get list all public keys related to client in service.
 *
 * @package CKPL\Pay\Merchant
 */
interface MerchantManagerInterface
{
    /**
     * Invalidates current authorization token.
     *
     * @return void
     */
    public function invalidateToken(): void;

    /**
     * Sets public key ID.
     *
     * WARNING!
     * This must be a existing public key ID from Payment Service.
     * Any other value or non-existing ID will cause an exception in later use.
     *
     * @param string $publicKeyId public key ID
     *
     * @return void
     */
    public function setPublicKeyId(string $publicKeyId): void;

    /**
     * Checks whether public key specified in configuration matches storage entries related to it.
     * This method does not verify if public key exists in Payment Service.
     *
     * @throws StorageException storage-level related problem e.g. read/write permission problem.
     *
     * @return bool
     */
    public function isPublicKeySynced(): bool;

    /**
     * Sends public key defined in configuration to Payment Service and saves received ID.
     *
     * @param string|null $publicKey Custom public key to send. If there is no value key from configuration will be
     *                               used.
     *
     * @throws Exception       library-level related problem e.g. invalid data model.
     * @throws ClientException request-level related problem e.g. HTTP errors, API errors.
     *
     * @return AddedKeyResponseModel
     */
    public function sendPublicKey(string $publicKey = null): AddedKeyResponseModel;
}
