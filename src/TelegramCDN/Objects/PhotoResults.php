<?php

    /** @noinspection PhpMissingFieldTypeInspection */


    namespace TelegramCDN\Objects;

    /**
     * Class PhotoResults
     * @package TelegramCDN\Objects
     */
    class PhotoResults
    {
        /**
         * Identifier for this file, which can be used to download or reuse the file
         *
         * @var string
         */
        public $FileID;

        /**
         * Photo width
         *
         * @var int
         */
        public $Width;

        /**
         * Photo height
         *
         * @var int
         */
        public $Height;

        /**
         * File size
         *
         * @var int|null
         */
        public $FileSize;

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                "file_id" => $this->FileID,
                "width" => $this->Width,
                "height" => $this->Height,
                "file_size" => $this->FileSize
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return PhotoResults
         */
        public function fromArray(array $data): PhotoResults
        {
            $PhotoResultsObject = new PhotoResults();

            if(isset($data["file_id"]))
                $PhotoResultsObject->FileID = $data["file_id"];

            if(isset($data["width"]))
                $PhotoResultsObject->Width = $data["width"];

            if(isset($data["height"]))
                $PhotoResultsObject->Height = $data["height"];

            if(isset($data["file_size"]))
                $PhotoResultsObject->FileSize = $data["file_size"];

            return $PhotoResultsObject;
        }
    }