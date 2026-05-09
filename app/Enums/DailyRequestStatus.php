<?php

namespace App\Enums;

enum DailyRequestStatus: string
{
    case CREATED = 'Created';
    case PENDING = 'Pending';
    case PRINTED = 'Printed';
    case CLOSED = 'Closed';
    case RECEIVED = 'Received';
}
