<?php

    require("ppm");

    ppm_import("net.intellivoid.telegram_cdn");

    $TelegramCDN = new \TelegramCDN\TelegramCDN("1204353955:AAF5onK1sP8W_X5iA_A1d9THccismKNcU38", [-1001485314519]);

    $Results = $TelegramCDN->uploadFile(__DIR__ . DIRECTORY_SEPARATOR . "secret_message.txt");
    $DownloadedFile = $TelegramCDN->downloadFile($Results);
    var_dump(file_get_contents($DownloadedFile));
    var_dump($DownloadedFile);
    unlink($DownloadedFile);