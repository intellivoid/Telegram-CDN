<?php


    namespace TelegramCDN\Exceptions;


    use Exception;
    use Throwable;

    /**
     * Class FileSecurityException
     * @package TelegramCDN\Exceptions
     */
    class FileSecurityException extends Exception
    {
        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * FileSecurityException constructor.
         * @param string $message
         * @param int $code
         * @param Throwable|null $previous
         */
        public function __construct($message = "", $code = 0, Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
            $this->message = $message;
            $this->code = $code;
            $this->previous = $previous;
        }
    }