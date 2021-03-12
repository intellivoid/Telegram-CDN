<?php

    require("ppm");

    ppm_import("net.intellivoid.telegram_cdn");

    $TelegramCDN = new \TelegramCDN\TelegramCDN("", [-1001485314519]);

    $TelegramCDN->uploadFile(__DIR__ . DIRECTORY_SEPARATOR . "example.jpg");