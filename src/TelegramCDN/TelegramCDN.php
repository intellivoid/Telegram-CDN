<?php


    namespace TelegramCDN;

    use Longman\TelegramBot\Entities\PhotoSize;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;
    use TelegramCDN\Exceptions\UploadError;
    use TelegramCDN\Objects\PhotoResults;

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
         * @return PhotoResults[]
         * @throws UploadError
         */
        public function uploadFile(string $path): array
        {
            $UploadResults = Request::sendPhoto([
                "chat_id" => $this->channels[array_rand($this->channels)],
                "photo" => $path
            ]);

            if($UploadResults->isOk() == false)
                throw new UploadError($UploadResults->getDescription(), $UploadResults->getErrorCode(), $UploadResults->getRawData());

            $Results = [];

            /** @var PhotoSize $photoSize */
            foreach($UploadResults->getResult()->getPhoto() as $photoSize)
            {
                $PhotoResultsObject = new PhotoResults();
                $PhotoResultsObject->FileID = $photoSize->getFileId();
                $PhotoResultsObject->FileSize = $photoSize->getFileSize();
                $PhotoResultsObject->Width = $photoSize->getWidth();
                $PhotoResultsObject->Height = $photoSize->getHeight();

                $Results[] = $PhotoResultsObject;
            }

            return $Results;
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