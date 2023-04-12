<?php

namespace App\Http\Enums;

enum UserType: string
{
    case ADMIN = 'admin';
    case LEAD = 'lead-nurse';
    case NURSE = 'nurse';
}
