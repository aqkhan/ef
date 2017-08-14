<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateController extends Controller
{

    /**
     * Get weeks in a year,
     *
     * @param  int  $year
     * @return array $weeks
     */
    public function get_weeks_in_year($year){

        $weeks = array();

        $now = Carbon::now();

        $curr_year = $now->year;

        $curr_month = $now->month;

        $flag = ($curr_year == $year) ? $curr_month : 12;

        for ($i = 1; $i <= $flag; $i++){

            $weeks = array_merge($weeks, $this->get_weeks_in_month($i, $year));

        }

        return $weeks;

    }

    /**
     * Get weeks in a year,
     *
     * @param  int  $year
     * @return array $weeks
     */
    public function get_weeks_for_payroll($year){

        $now = Carbon::now();

        if ($year < 2013 || $year > $now->year)

            return 'Invalid entry!';

        $weeks = array();

        $week = Carbon::createFromDate($year, 1, 1);

        $start = clone $week->startOfWeek();

        $end = clone $week->endOfWeek()->addWeek();

        for($i=1; $i <= 26; $i++){

            if (($year == $now->year && $start->month == $now->month && $start->day > $now->day) ||
                ($year == $now->year && $end->month >= $now->month))

                break;

            $date = $start->format('F d, Y') . ' - ' . $end->format('F d, Y');

            $index = $start->toDateString() . '>' . $end->toDateString();

            $weeks[$index] = $date;

            $start->addWeeks(2);

            $end->addWeeks(2);
        }

        return $weeks;

    }

    /**
     * Get weeks in a year,
     *
     * @param  int  $month
     * @param  int  $year
     * @return array $weeks
     */
    public function get_weeks_in_month($month, $year){

        $weeks = array();

        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $now = Carbon::now();

        for($j=1; $j <= $days; $j+=7 ){

            $time = Carbon::createFromDate($year, $month, $j);

            $start = Carbon::instance($time)->startOfWeek();

            $end = Carbon::instance($time)->endOfWeek();

            if($end->month != $month || ($month == $now->month && $year == $now->year && $j > $now->day))

                break;

            $date = $start->format('F d, Y') . ' - ' . $end->format('F d, Y');

            $index = $start->toDateString() . '>' . $end->toDateString();

            $weeks[$index] = $date;

        }

        return $weeks;

    }

    /**
     * Get weeks in a year,
     *
     * @param  int  $month
     * @param  int  $year
     * @return array $weeks
     */
    public function split_payroll_weeks($period){

        $weeks = array();

        $period = explode('>', $period);

        $first = Carbon::parse($period[0]);

        $second = Carbon::parse($period[1]);

        $week_1 = $first->startOfWeek()->toDateString() . '>' . $first->endOfWeek()->toDateString();

        $week_2 = $second->startOfWeek()->toDateString() . '>' . $second->endOfWeek()->toDateString();

        $weeks = [$week_1, $week_2];

        return $weeks;

    }

    /**
     * Get week alias.
     *
     * @param  int  $year
     * @param  int  $week
     * @return array index $week
     */
    public function get_week_alias($year, $week){

        $weeks = $this->get_weeks_in_year($year);

        return $weeks[$week];

    }

    /**
     * Get week alias.
     *
     * @param  int  $year
     * @param  int  $week
     * @return array index $week
     */
    public function payroll_week_alias($year, $week){

        $weeks = $this->get_weeks_for_payroll($year);

        return $weeks[$week];

    }
}
