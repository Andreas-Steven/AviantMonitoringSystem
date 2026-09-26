<?php

namespace App\Shared\Enums;

enum HttpStatusCode: int
{
    case Ok = 200;
    case Created = 201;
    case Unauthorized = 401;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case UnprocessableEntity = 422;
    case InternalServerError = 500;
    case ServiceUnavailable = 503;
}
