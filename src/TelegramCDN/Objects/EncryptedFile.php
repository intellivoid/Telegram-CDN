<?php

    /** @noinspection PhpUnused */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace TelegramCDN\Objects;

    /**
     * Class EncryptedFile
     * @package TelegramCDN\Objects
     */
    class EncryptedFile
    {
        /**
         * Identifier for this file, which can be used to download or reuse the file
         *
         * @var string
         */
        public $FileID;

        /**
         * Unique identifier for this file, which is supposed to be the same over time and for different bots. Can't be used to download or reuse the file.
         *
         * @var string
         */
        public $FileUniqueID;

        /**
         * MIME type of the file as defined by sender
         *
         * @var int
         */
        public $MimeType;

        /**
         * File size on the CDN
         *
         * @var int|null
         */
        public $CdnFileSize;

        /**
         * The hash of the file stored on the CDN
         *
         * @var string
         */
        public $CdnFileHash;

        /**
         * The original file size
         *
         * @var int|null
         */
        public $OriginalFileSize;

        /**
         * The original file hash
         *
         * @var string
         */
        public $OriginalFileHash;

        /**
         * The encryption key used to encrypt the contents of the file
         *
         * @var string
         */
        public $EncryptionKey;

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                "file_id" => $this->FileID,
                "file_unique_id" => $this->FileUniqueID,
                "mime_type" => $this->MimeType,
                "cdn_file_size" => $this->CdnFileSize,
                "cdn_file_hash" => $this->CdnFileHash,
                "original_file_size" => $this->OriginalFileSize,
                "original_file_hash" => $this->OriginalFileHash,
                "encryption_key" => $this->EncryptionKey
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return EncryptedFile
         */
        public static function fromArray(array $data): EncryptedFile
        {
            $EncryptedFileObject = new EncryptedFile();

            if(isset($data["file_id"]))
                $EncryptedFileObject->FileID = $data["file_id"];

            if(isset($data["file_unique_id"]))
                $EncryptedFileObject->FileUniqueID = $data["file_unique_id"];

            if(isset($data["mime_type"]))
                $EncryptedFileObject->MimeType = $data["mime_type"];

            if(isset($data["cdn_file_size"]))
                $EncryptedFileObject->CdnFileSize = $data["cdn_file_size"];

            if(isset($data["cdn_file_hash"]))
                $EncryptedFileObject->CdnFileHash = $data["cdn_file_hash"];

            if(isset($data["original_file_size"]))
                $EncryptedFileObject->OriginalFileSize = $data["original_file_size"];

            if(isset($data["original_file_hash"]))
                $EncryptedFileObject->OriginalFileHash = $data["original_file_hash"];

            if(isset($data["encryption_key"]))
                $EncryptedFileObject = $data["encryption_key"];

            return $EncryptedFileObject;
        }
    }