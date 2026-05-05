<?php

namespace App\Enums;

enum DailyRequesStatus: string
{
    case CREATED = 'Created';
    case PRINTED = 'Printed';
    case CLOSED = 'Closed';
    case RECEIVED = 'Received';
}
