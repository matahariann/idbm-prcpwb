<?php

namespace App\Enums;

enum DailyRequestStatus: string
{
    case CREATED = 'Created';
    case PRINTED = 'Printed';
    case CLOSED = 'Closed';
    case RECEIVED = 'Received';
}
