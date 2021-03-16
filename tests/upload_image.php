<?php

    require("ppm");

    ppm_import("net.intellivoid.telegram_cdn");

    $TelegramCDN = new \TelegramCDN\TelegramCDN("", [-1001485314519]);

    $Results = $TelegramCDN->uploadFile(__DIR__ . DIRECTORY_SEPARATOR . "example_2.png");
    var_dump($TelegramCDN->getFileUrl($Results->FileID));