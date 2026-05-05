<?php

namespace App\Enums;

enum POStatus: string
{
    case NEW = 'New';
    case CONFIRM = 'Confirmed';
    case REVISED = 'Revised';
    case CANCELLED = 'Cancelled';
}
