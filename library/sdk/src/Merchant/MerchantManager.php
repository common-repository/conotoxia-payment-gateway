<?php

declare(strict_types=1);

namespace CKPL\Pay\Merchant;

use CKPL\Pay\Endpoint\SendPublicKeyEndpoint;
use CKPL\Pay\Exception\ClientException;
use CKPL\Pay\Exception\Exception;
use CKPL\Pay\Exception\StorageException;
use CKPL\Pay\Model\Response\AddedKeyResponseModel;
use CKPL\Pay\Service\BaseService;
use CKPL\Pay\Storage\StorageInterface;
use function hash;

/**
 * Class MerchantManager.
 *
 * Merchant related functionality such as
 * ability to check if public key is synchronized with
 * service, send public key to service, get ID for public key
 * if already sent to service, get list all public keys related to client in service.
 *
 * @package CKPL\Pay\Merchant
 */
class MerchantManager extends BaseService implements MerchantManagerInterface
{
    /**
     * @type string
     */
    protected const ALGORITHM = 'SHA256';

    /**
     * Invalidates current authorization token.
     *
     * @return void
     */
    public function invalidateToken(): void
    {
        $this->dependencyFactory->getSecurityManager()->invalidateToken();
    }

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
    public function setPublicKeyId(string $publicKeyId): void
    {
        $publicKeyChecksum = hash(static::ALGORITHM, $this->configuration->getPublicKey());

        $this->configuration->getStorage()->setItem(StorageInterface::PUBLIC_KEY_ID, $publicKeyId);
        $this->configuration->getStorage()->setItem(StorageInterface::PUBLIC_KEY_CHECKSUM, $publicKeyChecksum);
    }

    /**
     * Checks whether public key specified in configuration matches storage entries related to it.
     * This method does not verify if public key exists in Payment Service.
     *
     * @throws StorageException storage-level related problem e.g. read/write permission problem.
     *
     * @return bool
     */
    public function isPublicKeySynced(): bool
    {
        $publicKeyStorageHash = $this->configuration->getStorage()->hasItem(StorageInterface::PUBLIC_KEY_CHECKSUM)
            ? $this->configuration->getStorage()->expectStringOrNull(StorageInterface::PUBLIC_KEY_CHECKSUM)
            : null;

        $publicKeyConfigurationHash = hash(static::ALGORITHM, $this->configuration->getPublicKey());

        return $publicKeyConfigurationHash === $publicKeyStorageHash;
    }

    /**
     * Sends public key defined in configuration to Payment Service and saves received ID.
     *
     * @param string|null $publicKey Custom public key to send. If there is no value key from configuration will be
     *                               used.
     *
     * @throws ClientException request-level related problem e.g. HTTP errors, API errors.
     * @throws Exception       library-level related problem e.g. invalid data model.
     *
     * @return void
     */
    public function sendPublicKey(string $publicKey = null): AddedKeyResponseModel
    {
        $publicKey = $publicKey ?? $this->configuration->getPublicKey();

        $client = $this->dependencyFactory->createClient(
            new SendPublicKeyEndpoint(),
            $this->configuration,
            $this->dependencyFactory->getSecurityManager(),
            $this->dependencyFactory->getAuthenticationManager()
        );

        $client->request()->parameters([
            'public_key' => $publicKey,
        ])->send();

        $publicKeyModel = $client->getResponse()->getProcessedOutput();

        if ($publicKeyModel instanceof AddedKeyResponseModel) {
            $publicKeyHash = hash(static::ALGORITHM, $publicKey);

            $this->configuration->getStorage()->setItem(StorageInterface::PUBLIC_KEY_ID, $publicKeyModel->getKeyId());
            $this->configuration->getStorage()->setItem(StorageInterface::PUBLIC_KEY_CHECKSUM, $publicKeyHash);
            return $publicKeyModel;
        }
        throw new Exception(static::UNSUPPORTED_RESPONSE_MODEL_EXCEPTION);
    }
}
