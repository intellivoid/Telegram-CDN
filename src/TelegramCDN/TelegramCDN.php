<?php

    namespace TelegramCDN;

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
        public function __construct(string $bot_api, array $channels = [])
        {
            $this->channels = $channels;
            $this->telegram = new Telegram($bot_api, (string) null);
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
         * @noinspection DuplicatedCode
         */
        public function uploadFileEncrypted(string $path) : EncryptedFile
        {
            // Start to get information about the original file
            $EncryptedFile = new EncryptedFile();
            $EncryptedFile->OriginalFileHash = hash_file("sha1", $path);
            $EncryptedFile->OriginalFileSize = filesize($path);
            $EncryptedFile->MimeType = MimeLib::detectFileType($path)->getMime();

            // Create a temporary file
            $EncryptedFileTmp = new TmpFile(null);

            // Encrypt the file
            $EncryptionKey = Key::createNewRandomKey();
            File::encryptFile($path, $EncryptedFileTmp->getFileName(), $EncryptionKey);

            // Record the encryption results
            $EncryptedFile->EncryptionKey = $EncryptionKey->saveToAsciiSafeString();
            $EncryptedFile->CdnFileHash = hash_file("sha1", $EncryptedFileTmp->getFileName());
            $EncryptedFile->CdnFileSize = filesize($EncryptedFileTmp->getFileName());

            // Upload the data
            $UploadResults = Request::sendDocument(["chat_id" => $this->channels[array_rand($this->channels)], "document" => $EncryptedFileTmp->getFileName()]);

            // Delete temporary file
            unlink($EncryptedFileTmp->getFileName());
            if ($UploadResults->isOk() == false)
                throw new UploadError($UploadResults->getDescription(), $UploadResults->getErrorCode(), $UploadResults->getRawData());

            /** @var Message $Message */
            $Message = $UploadResults->getResult();
            $EncryptedFile->FileUniqueID = $Message->getDocument()->getRawData()["file_unique_id"];
            $EncryptedFile->FileID = $Message->getDocument()->getFileId();
            return $EncryptedFile;
        }
        /**
         * Uploads a file to Telegram's servers without encryption
         *
         * @param string $path
         * @return Objects\File
         * @throws CannotDetectFileTypeException
         * @throws FileNotFoundException
         * @throws UploadError
         * @noinspection DuplicatedCode
         */
        public function uploadFile(string $path) : Objects\File
        {
            // Start to get information about the original file
            $File = new Objects\File();
            $File->OriginalFileHash = hash_file("sha1", $path);
            $File->OriginalFileSize = filesize($path);
            $File->MimeType = MimeLib::detectFileType($path)->getMime();

            // Upload the data
            $UploadResults = Request::sendDocument(["chat_id" => $this->channels[array_rand($this->channels)], "document" => $path]);

            if ($UploadResults->isOk() == false)
                throw new UploadError($UploadResults->getDescription(), $UploadResults->getErrorCode(), $UploadResults->getRawData());

            /** @var Message $Message */
            $Message = $UploadResults->getResult();
            $File->FileUniqueID = $Message->getDocument()->getRawData()["file_unique_id"];
            $File->FileID = $Message->getDocument()->getFileId();
            return $File;
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
        public function downloadEncryptedFile(EncryptedFile $encryptedFile, bool $verify_integrity = True, $download_url = null) : string
        {
            if ($download_url == null)
                $download_url = $this->getFileUrl($encryptedFile->FileID);

            // Create a temporary file
            $DecryptedFileTmp = new TmpFile(null);
            $DecryptedFileTmp->delete = false;
            $DownloadedFile = $this->downloadFileStream($download_url);
            $EncryptionKey = Key::loadFromAsciiSafeString($encryptedFile->EncryptionKey);

            if (hash_file("sha1", $DownloadedFile) !== $encryptedFile->CdnFileHash && $verify_integrity)
                throw new FileSecurityException("The file has been modified by the CDN, hash check failed.");

            File::decryptFile($DownloadedFile, $DecryptedFileTmp->getFileName(), $EncryptionKey);
            unlink($DownloadedFile);

            if (hash_file("sha1", $DecryptedFileTmp->getFileName()) !== $encryptedFile->OriginalFileHash && $verify_integrity)
                throw new FileSecurityException("The file has been modified by the decipher, hash check failed.");

            return $DecryptedFileTmp->getFileName();
        }
        /**
         * Downloads a unencrypted file to disk
         *
         * @param Objects\File $encryptedFile
         * @param bool $verify_integrity
         * @param null $download_url
         * @return string
         * @throws FileSecurityException
         * @throws TelegramException
         */
        public function downloadFile(Objects\File $encryptedFile, bool $verify_integrity = True, $download_url = null) : string
        {
            if ($download_url == null)
            {
                $download_url = $this->getFileUrl($encryptedFile->FileID);
            }

            $DownloadedFile = $this->downloadFileStream($download_url);

            if (hash_file("sha1", $DownloadedFile) !== $encryptedFile->OriginalFileHash && $verify_integrity)
            {
                throw new FileSecurityException("The file has been modified by the CDN, hash check failed.");
            }

            return $DownloadedFile;
        }

        /**
         * Downloads a file stream
         *
         * @param string $location
         * @return string
         */
        private function downloadFileStream(string $location) : string
        {
            $TemporaryFile = new TmpFile(null);
            $TemporaryFile->delete = false;
            //This is the file where we save the    information
            $fp = fopen($TemporaryFile->getFileName(), 'w+');
            $ch = curl_init(str_replace(" ", "%20", $location));
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            return $TemporaryFile->getFileName();
        }

        /**
         * Gets the direct URL for the file
         *
         * @param string $file_id
         * @return string
         * @throws TelegramException
         */
        public function getFileUrl(string $file_id) : string
        {
            return Request::downloadFileLocation(Request::getFile(["file_id" => $file_id])->getResult());
        }

        /**
         * @return Telegram
         * @noinspection PhpUnused
         */
        public function getTelegram() : Telegram
        {
            return $this->telegram;
        }
    }