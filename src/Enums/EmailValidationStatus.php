<?php

namespace Jojostx\ElasticEmail\Enums;

enum EmailValidationStatus: string
{
    case VALID = 'valid';
    case INVALID = 'invalid';
    case RISKY = 'risky';
    case UNKNOWN = 'unknown';
    case NONE = 'none';
}
