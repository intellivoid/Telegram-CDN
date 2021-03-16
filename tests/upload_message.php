<?php

    require("ppm");

    ppm_import("net.intellivoid.telegram_cdn");

    $TelegramCDN = new \TelegramCDN\TelegramCDN("", [-1001485314519]);

    $Results = $TelegramCDN->uploadFile(__DIR__ . DIRECTORY_SEPARATOR . "secret_message.txt");
    var_dump($TelegramCDN->decryptFile($Results));