<?php
// src/Enum/DeletionStatus.php

namespace App\Enum;

enum DeletionStatus: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}

?>