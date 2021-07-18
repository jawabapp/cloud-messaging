<?php

if (!function_exists('csv')) {

    function csv($array, $headers = [])
    {
        if (empty($array)) {
            return '';
        }

        foreach ($array as &$item) {
            foreach ($item as $key => &$value) {
                //fix value
                $value = str_replace(',', '-', $value);
            }
        }

        if (!empty($headers)) {
            $txt = implode(',', $headers) . PHP_EOL;
        } else {
            $txt = implode(',', array_keys((array) $array[0])) . PHP_EOL;
        }
        foreach ($array as $line) {
            $txt .= implode(',', array_values((array) $line)) . PHP_EOL;
        }

        return $txt;
    }
}
