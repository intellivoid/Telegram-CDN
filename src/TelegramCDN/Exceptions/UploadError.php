<?php

    /** @noinspection PhpMissingFieldTypeInspection */


    namespace TelegramCDN\Exceptions;

    use Exception;
    use Throwable;

    /**
     * Class UploadError
     * @package TelegramCDN\Exceptions
     */
    class UploadError extends Exception
    {
        /**
         * @var array|null
         */
        private $raw;

        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * UploadError constructor.
         * @param string $message
         * @param int $code
         * @param null $raw
         * @param Throwable|null $previous
         */
        public function __construct($message = "", $code = 0, $raw=null, Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
            $this->message = $message;
            $this->code = $code;
            $this->raw = $raw;
            $this->previous = $previous;
        }
    }