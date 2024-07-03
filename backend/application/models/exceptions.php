<?php
defined('BASEPATH') || exit('No direct script access allowed');

class ValidationException extends Exception
{
}

class DuplicateException extends ValidationException
{
}

class NotFoundException extends ValidationException
{
}
