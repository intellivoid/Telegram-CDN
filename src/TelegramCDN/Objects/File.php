<?php

    /** @noinspection PhpUnused */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace TelegramCDN\Objects;

    /**
     * Class File
     * @package TelegramCDN\Objects
     */
    class File
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
                "original_file_size" => $this->OriginalFileSize,
                "original_file_hash" => $this->OriginalFileHash
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return File
         * @noinspection DuplicatedCode
         */
        public static function fromArray(array $data): File
        {
            $FileObject = new File();

            if(isset($data["file_id"]))
                $FileObject->FileID = $data["file_id"];

            if(isset($data["file_unique_id"]))
                $FileObject->FileUniqueID = $data["file_unique_id"];

            if(isset($data["mime_type"]))
                $FileObject->MimeType = $data["mime_type"];

            if(isset($data["original_file_size"]))
                $FileObject->OriginalFileSize = $data["original_file_size"];

            if(isset($data["original_file_hash"]))
                $FileObject->OriginalFileHash = $data["original_file_hash"];

            return $FileObject;
        }
    }