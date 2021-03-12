<?php

    require("ppm");

    ppm_import("net.intellivoid.telegram_cdn");

    $TelegramCDN = new \TelegramCDN\TelegramCDN("", [-1001485314519]);

    var_dump(\Longman\TelegramBot\Request::getMe());