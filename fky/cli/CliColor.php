#!/usr/bin/env php
<?php

class CliColor {
    const CSI = "\033[";
    const CEND = self::CSI . "0m";
    const CDGREEN = self::CSI . "32m";
    const CRED = self::CSI . "1;31m";
    const CGREEN = self::CSI . "1;32m";
    const CYELLOW = self::CSI . "1;33m";
    const CBLUE = self::CSI . "1;34m";
    const CMAGENTA = self::CSI . "1;35m";
    const CCYAN = self::CSI . "1;36m";
    const CSUCCESS = self::CDGREEN;
    const CFAILURE = self::CRED;
    const CQUESTION = self::CMAGENTA;
    const CWARNING = self::CYELLOW;
    const CMSG = self::CCYAN;

    //进度条
    public static function progressbar($total, $description = 'running')
    {
        static $current = 0;
        $current++;
        $length     = 49;
        $percentage = sprintf('%.4f', number_format($current / $total, 4));
        $cell_num   = ceil($percentage * $length);
        $pad        = strlen((string)$total);
        $des_pad    = strlen($description);
        $cell       = '';
        $end        = $current == $total ? PHP_EOL : "";

        for ($i = 0; $i < $cell_num; $i++) {
            $cell .= sprintf(CliColor::CGREEN . "%s" . CliColor::CEND, '█');
        }
        $empty = '';
        for ($i = 0; $i < $length - $cell_num; $i++) {
            $empty .= '█';
        }
        $cmd_text = sprintf('%.' . $des_pad . 's> %s%s[%.' . $pad . 's/%.' . $pad . 's %.2f%%] ', $description, $cell, $empty, $current, $total, (100 * $percentage));
        echo "\r" . $cmd_text;
        echo $end;
    }
}
