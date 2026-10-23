<?php

if (!function_exists('isWarehouseValid')) {
    function isWarehouseValid($lat, $lon, $storage, $distance = null) {
        if (!is_numeric($lat) || !is_numeric($lon) || !is_numeric($storage)) {
            return false;
        }
        if ($distance !== null && (!is_numeric($distance) || (float)$distance < 0)) {
            return false;
        }
        $lat = (float)$lat;
        $lon = (float)$lon;
        $storage = (float)$storage;

        return ($lat > 0 && $lat < 40)
            && ($lon > 65 && $lon <= 180)
            && ($storage >= 0);
    }
}

if (!function_exists('isFPSValid')) {
    function isFPSValid($lat, $lon, $demand) {
        if (!is_numeric($lat) || !is_numeric($lon) || !is_numeric($demand)) {
            return false;
        }
        $lat = (float)$lat;
        $lon = (float)$lon;
        $demand = (float)$demand;

        return ($lat > 0 && $lat < 40)
            && ($lon > 65 && $lon <= 180)
            && ($demand >= 0);
    }
}

if (!function_exists('isDCPValid')) {
    function isDCPValid($lat, $lon, $demand) {
        return isFPSValid($lat, $lon, $demand);
    }
}

if (!function_exists('isFCIValid')) {
    function isFCIValid($lat, $lon, $demand) {
        return isFPSValid($lat, $lon, $demand);
    }
}

if (!function_exists('getWarehouseValidSqlCondition')) {
    function getWarehouseValidSqlCondition() {
        return "(latitude REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND (latitude + 0) > 0 AND (latitude + 0) < 40) " .
               "AND (longitude REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND (longitude + 0) > 65 AND (longitude + 0) <= 180) " .
               "AND (storage REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND (storage + 0) >= 0)";
    }
}

if (!function_exists('getFPSValidSqlCondition')) {
    function getFPSValidSqlCondition() {
        return "(latitude REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND (latitude + 0) > 0 AND (latitude + 0) < 40) " .
               "AND (longitude REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND (longitude + 0) > 65 AND (longitude + 0) <= 180) " .
               "AND (demand REGEXP '^[0-9]+(\\\\.[0-9]+)?$' AND (demand + 0) >= 0)";
    }
}

if (!function_exists('getDCPValidSqlCondition')) {
    function getDCPValidSqlCondition() {
        return getFPSValidSqlCondition();
    }
}

if (!function_exists('getFCIValidSqlCondition')) {
    function getFCIValidSqlCondition() {
        return getFPSValidSqlCondition();
    }
}
