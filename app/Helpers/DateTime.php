<?php

use Carbon\Carbon;

/**
 * Date/Time helper functions
 */

/**
 * Parse time alias from Carbon
 *
 * @param  string $string
 * @return Carbon
 */
function parseDateTime($string)
{
    return Carbon::parse($string);
}

/**
 * Print the month and year given the date time string
 * e.g. 01-2017
 *
 * @param  string $stringDateTime
 * @return string
 */
function toMonthYear($stringDateTime)
{
    return Carbon::parse($stringDateTime)->format('m-Y');
}


/**
 * Print the date time string
 *
 * @param  string $stringDateTime
 * @return string
 */
function toDateTime($stringDateTime)
{
    return Carbon::parse($stringDateTime)->toDateTimeString();
}

/**
 * Print the time string
 *
 * @param  string $stringDateTime
 * @return string
 */
function toTime($stringDateTime, $hourMinute = null)
{
    $time = Carbon::parse($stringDateTime);

    // If the last param is set
    if (isset($hourMinute)) {
        if ($hourMinute) {
            // Short 12 hour format
            return $time->format('h:i A');
        }
        else {
            // Long 24 hour format
            return $time->format('H:i');
        }
    }

    // Otherwise deafault to normal format
    return $time->toTimeString();
}

/**
 * Print the date string
 *
 * @param  string $stringDateTime
 * @return string
 */
function toDate($stringDateTime, $brackets = null)
{
    $time = Carbon::parse($stringDateTime);

    // If the last param is set
    if (isset($brackets)) {
        if ($brackets) {
            // Short 12 hour format
            return $time->format('d/m/Y');
        }
        else {
            // Long 24 hour format
            return $time->format('d-m-Y');
        }
    }

    return $time->toDateString();
}

/**
 * Get current time now in AEST
 *
 * @return string
 */
function getTimeNow()
{
    return Carbon::now('AEST')->toTimeString();
}

/**
 * Get current date now in AEST
 *
 * @return string
 */
function getDateNow()
{
    return Carbon::now('AEST')->toDateString();
}

/**
 * Get current date time now in AEST
 *
 * @return string
 */
function getDateTimeNow()
{
    return Carbon::now('AEST')->toDateTimeString();
}