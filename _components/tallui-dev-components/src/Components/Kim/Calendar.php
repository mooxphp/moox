<?php

declare(strict_types=1);

namespace Usetall\TalluiDevComponents\Components\Kim;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Usetall\TalluiDevComponents\Components\LivewireComponent;

class Calendar extends LivewireComponent
{
    /** @var array */
    protected static $assets = ['example'];

    public $month;

    public $year;

    //stuff view need

    public $today;

    public $monthname;

    public $onemonth;

    public $events;

    public $subject;

    public $startEvent;

    public $endEvent;

    public $body;

    public $visible;

    public function days()
    {
        $this->monthname = $this->getMonthName($this->month);
        $this->buildOneMonth();
        // $this->events = DB::table('events')->get();
    }

    public function buildOneMonth()
    {
        $this->onemonth = null;
        $predays = $this->getDaysPreviousetMonth($this->month);
        if (isset($predays)) {
            foreach ($predays as $preday) {
                $this->onemonth[] = $preday;
            }
        }
        $days = $this->getDaysCurrentMonth($this->month);
        foreach ($days as $day) {
            $this->onemonth[] = $day;
        }
    }

    public function getDaysCurrentMonth($month)
    {
        $daysinmonth = $this->getDaysinMonth($month);
        for ($count = 1; $count <= $daysinmonth; $count++) {
            $date = $this->createDate($month, $this->year, $count);
            $days[$count] = 'cur '.$date->shortEnglishDayOfWeek.' '.$date;
        }

        return $days;
    }

    public function getDaysPreviousetMonth($month)
    {
        $date = $this->createDate($month, $this->year, 1);
        $dayspreviouse = $this->getDaysFromPreviouseMonth($date->englishDayOfWeek);
        $getpreviousemonthdays = $this->getDaysinMonth($month - 1);
        for ($count = ($getpreviousemonthdays - $dayspreviouse) + 1; $count <= $getpreviousemonthdays; $count++) {
            $date = $this->createDate($month - 1, $this->year, $count);
            $predays[$count] = 'pre '.$date->shortEnglishDayOfWeek.' '.$date;
        }

        if (isset($predays)) {
            return $predays;
        }
    }

    public function getDaysFromPreviouseMonth($name)
    {
        $dayspreviouse = 0;
        switch ($name) {
            case 'Monday':
                $dayspreviouse = 0;
                break;
            case 'Tuesday':
                $dayspreviouse = 1;
                break;
            case 'Wednesday':
                $dayspreviouse = 2;
                break;
            case 'Thursday':
                $dayspreviouse = 3;
                break;
            case 'Friday':
                $dayspreviouse = 4;
                break;
            case 'Saturday':
                $dayspreviouse = 5;
                break;
            case 'Sunday':
                $dayspreviouse = 6;
                break;
        }

        return $dayspreviouse;
    }

    public function getDaysinMonth($month)
    {
        $date = $this->createDate($month, $this->year, 1);

        return $date->daysInMonth;
    }

    public function createDate($month, $year, $day)
    {
        $date = Carbon::createFromFormat('m/d/Y', $month.'/'.$day.'/'.$year);

        return $date;
    }

    public function getMonthName($month)
    {
        $date = $this->createDate($month, $this->year, 1);

        return $date->englishMonth;
    }

    //click Functions

    public function previouseMonth()
    {
        if ($this->month != 1) {
            $this->month--;
        } else {
            $this->month = 12;
            $this->year--;
        }
        $this->days();
    }

    public function nextMonth()
    {
        if ($this->month != 12) {
            $this->month++;
        } else {
            $this->month = 1;
            $this->year++;
        }

        $this->days($this->month);
    }

    public function today()
    {
        if (! isset($this->today)) {
            $this->today = Carbon::now();
        }

        $this->day = $this->today->day;
        $this->month = $this->today->month;
        $this->year = $this->today->year;
    }

    public function addEvent()
    {
        DB::table('events')->insert([
            'subject' => $this->subject,
            'event_start' => $this->startEvent,
            'event_end' => $this->endEvent,
            'time_start' => substr($this->startEvent, 11, 5),
            'time_end' => substr($this->endEvent, 11, 5),
            'body' => $this->body,
        ]);
    }

    public function setModalVisible():void
    {
    }

    public function render()
    {
        if (! isset($this->today)) {
            $this->today();
        }
        $this->days();

        return view('tallui-dev-components::components.kim.calendar');
    }
}
