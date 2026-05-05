<?php

namespace App\Enums;

enum ForecastStatus: string
{
    case NEW = 'New';
    case READ = 'Read';
    case CONFIRMED = 'Confirmed';
}
