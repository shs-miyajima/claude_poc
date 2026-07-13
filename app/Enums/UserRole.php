<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperUser = 'super_user';
    case Admin = 'admin';
    case User = 'user';
}
