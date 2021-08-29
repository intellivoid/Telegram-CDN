<?php


    namespace TelegramCDN;

    use Defuse\Crypto\Crypto;
    use Defuse\Crypto\Exception\BadFormatException;
    use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
    use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
    use Defuse\Crypto\Key;
    use Longman\TelegramBot\Entities\Message;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;
    use TelegramCDN\Exceptions\FileSecurityException;
    use TelegramCDN\Exceptions\UploadError;
    use TelegramCDN\Objects\EncryptedFile;

    /**
     * Class TelegramCDN
     * @package TelegramCDN
     */
    class TelegramCDN
    {
        /**
         * The Bot API used to access Telegram's servers
         *
         * @var string
         */
        private string $bot_api;

        /**
         * Channels to upload content to
         *
         * @var int[]
         */
        private array $channels;

        /**
         * @var Telegram
         */
        private Telegram $telegram;

        /**
         * TelegramCDN constructor.
         * @param string $bot_api
         * @param int[] $channels
         * @throws TelegramException
         */
        public function __construct(string $bot_api, array $channels=[])
        {
            $this->bot_api = $bot_api;
            $this->channels = $channels;
            $this->telegram = new Telegram($bot_api, "", false);
        }

        /**
         * Uploads a file to Telegram's servers
         *
         * @param string $path
         * @return EncryptedFile
         * @throws UploadError
         * @throws EnvironmentIsBrokenException
         */
        public function uploadFile(string $path): EncryptedFile
        {
            // Start to get information about the original file
            $EncryptedFile = new EncryptedFile();
            $EncryptedFile->OriginalFileHash = hash("sha256", file_get_contents($path));
            $EncryptedFile->OriginalFileSize = strlen(file_get_contents($path));
            $EncryptedFile->MimeType = mime_content_type($path);

            // Encrypt the file
            $EncryptionKey = Key::createNewRandomKey();
            $EncryptedData = Crypto::encrypt(file_get_contents($path), $EncryptionKey, true);
            $EncryptedFilePath = $path . ".bin";

            // Save the file to disk temporarily
            file_put_contents($EncryptedFilePath, $EncryptedData);

            // Record the encryption results
            $EncryptedFile->EncryptionKey = $EncryptionKey->saveToAsciiSafeString();
            $EncryptedFile->CdnFileHash = hash("sha256", $EncryptedData);
            $EncryptedFile->CdnFileSize = strlen($EncryptedData);

            // Upload the data
            $UploadResults = Request::sendDocument([
                "chat_id" => $this->channels[array_rand($this->channels)],
                "document" => $EncryptedFilePath
            ]);

            // Delete temporary file
            unlink($EncryptedFilePath);

            if($UploadResults->isOk() == false)
                throw new UploadError($UploadResults->getDescription(), $UploadResults->getErrorCode(), $UploadResults->getRawData());

            /** @var Message $Message */
            $Message = $UploadResults->getResult();
            $EncryptedFile->FileUniqueID = $Message->getDocument()->getRawData()["file_unique_id"];
            $EncryptedFile->FileID = $Message->getDocument()->getFileId();

            return $EncryptedFile;
        }

        /**
         * Downloads the file from the CDN and decrypts the contents via inline memory
         *
         * @param EncryptedFile $encryptedFile
         * @param bool $verify_integrity
         * @param null $download_url
         * @return string
         * @throws BadFormatException
         * @throws EnvironmentIsBrokenException
         * @throws FileSecurityException
         * @throws TelegramException
         * @throws WrongKeyOrModifiedCiphertextException
         */
        public function decryptFile(EncryptedFile $encryptedFile, bool $verify_integrity=True, $download_url=null)
        {
            if($download_url == null)
                $download_url = $this->getFileUrl($encryptedFile->FileID);
            $FileContents = file_get_contents($download_url);

            if(hash("sha256", $FileContents) !== $encryptedFile->CdnFileHash && $verify_integrity)
                throw new FileSecurityException("The file has been modified by the CDN, hash check failed.");

            $EncryptionKey = Key::loadFromAsciiSafeString($encryptedFile->EncryptionKey);
            $DecryptedContents = Crypto::decrypt($FileContents, $EncryptionKey, true);

            if(hash("sha256", $DecryptedContents) !== $encryptedFile->OriginalFileHash && $verify_integrity)
                throw new FileSecurityException("The file has been modified by the decipher, hash check failed.");

            return $DecryptedContents;
        }

        /**
         * Gets the direct URL for the file
         *
         * @param string $file_id
         * @return string
         * @throws TelegramException
         */
        public function getFileUrl(string $file_id): string
        {
            return Request::downloadFileLocation(Request::getFile(["file_id" => $file_id])->getResult());
        }

        /**
         * @return Telegram
         */
        public function getTelegram(): Telegram
        {
            return $this->telegram;
        }
    }