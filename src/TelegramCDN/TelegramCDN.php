<?php


    namespace TelegramCDN;

    use Defuse\Crypto\Crypto;
    use Defuse\Crypto\Exception\BadFormatException;
    use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
    use Defuse\Crypto\Exception\IOException;
    use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
    use Defuse\Crypto\File;
    use Defuse\Crypto\Key;
    use Longman\TelegramBot\Entities\Message;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;
    use MimeLib\Exceptions\CannotDetectFileTypeException;
    use MimeLib\Exceptions\FileNotFoundException;
    use MimeLib\MimeLib;
    use TelegramCDN\Exceptions\FileSecurityException;
    use TelegramCDN\Exceptions\UploadError;
    use TelegramCDN\Objects\EncryptedFile;
    use TmpFile\TmpFile;

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
         * @throws EnvironmentIsBrokenException
         * @throws IOException
         * @throws UploadError
         * @throws CannotDetectFileTypeException
         * @throws FileNotFoundException
         */
        public function uploadFile(string $path): EncryptedFile
        {
            // Start to get information about the original file
            $EncryptedFile = new EncryptedFile();
            $EncryptedFile->OriginalFileHash = hash_file("sha256", $path);
            $EncryptedFile->OriginalFileSize = filesize($path);
            $EncryptedFile->MimeType = MimeLib::detectFileType($path)->getMime();

            // Create a temporary file
            $EncryptedFileTmp = new TmpFile(null);

            // Encrypt the file
            $EncryptionKey = Key::createNewRandomKey();
            File::encryptFile($path, $EncryptedFileTmp->getFileName(), $EncryptionKey);

            // Record the encryption results
            $EncryptedFile->EncryptionKey = $EncryptionKey->saveToAsciiSafeString();
            $EncryptedFile->CdnFileHash = hash_file("sha256", $EncryptedFileTmp->getFileName());
            $EncryptedFile->CdnFileSize = filesize($EncryptedFileTmp->getFileName());

            // Upload the data
            $UploadResults = Request::sendDocument([
                "chat_id" => $this->channels[array_rand($this->channels)],
                "document" => $EncryptedFileTmp->getFileName()
            ]);

            // Delete temporary file
            unlink($EncryptedFileTmp->getFileName());

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
         * @throws IOException
         * @throws TelegramException
         * @throws WrongKeyOrModifiedCiphertextException
         */
        public function decryptFile(EncryptedFile $encryptedFile, bool $verify_integrity=True, $download_url=null): string
        {
            if($download_url == null)
                $download_url = $this->getFileUrl($encryptedFile->FileID);
            $FileResource = fopen($download_url, 'rb');

            // Create a temporary file
            $DecryptedFileTmp = new TmpFile(null);
            $DecryptedFileTmp->delete = false;
            $DecryptedFileResource = fopen($DecryptedFileTmp->getFileName(), 'wb');
            $EncryptionKey = Key::loadFromAsciiSafeString($encryptedFile->EncryptionKey);
            File::decryptResource($FileResource, $DecryptedFileResource, $EncryptionKey);

            fclose($DecryptedFileResource);
            fclose($FileResource);

            if(hash_file("sha256", $DecryptedFileTmp->getFileName()) !== $encryptedFile->CdnFileHash && $verify_integrity)
                throw new FileSecurityException("The file has been modified by the CDN, hash check failed.");

            return $DecryptedFileTmp->getFileName();
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