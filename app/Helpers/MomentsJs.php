<?php

namespace App\Helpers;

use DateTime;
use stdClass;

class MomentsJs 
{
   public $months;


   public static function isLeapYear($year)
   {
       return ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
   }

   public static function getDaysInMonth($month, $year)
   {
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        $days = [];
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = new stdClass();
            $date->fday = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $date->value = 0;
            $date->idk = sprintf('%02d' ,$day);
            $days[$date->fday] = json_decode(json_encode($date), true);
        }

        return collect($days);
    }

    public static function getMonthsInYear()
    {
        $months = [
            1  => "January",
            2  => "February",
            3  => "March",
            4  => "April",
            5  => "May",
            6  => "June",
            7  => "July",
            8  => "August",
            9  => "September",
            10 => "October",
            11 => "November",
            12 => "December"
        ];

        return collect($months);
    }

        //  $date = date_parse($month_name);
 
        //  $month = $date['month'];         
        //  $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
 
        //  $days = [];
 
        //  for ($day = 1; $day <= $days_in_month; $day++) {
        //     $date = new stdClass();
        //      $date->fday = sprintf('%04d-%02d-%02d', $year, $month, $day);
        //      $date->value = 0;
        //      $date->idk = sprintf('%02d' ,$day);
        //      //$date->toArray();
        //      $days[$date->fday] = json_decode(json_encode($date), true);
 
        //  }
 
        //  return collect($days);
    // }

}
