<?php
// src/Enum/Status.php
namespace App\Enum;

enum Status: string
{
    case AVAILABLE = 'available';
    case BORROWED = 'borrowed';
}
?>