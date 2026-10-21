<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrador = 'administrador';
    case Editor = 'editor';
    case Autor = 'autor';
    case Leitor = 'leitor';
}
