<?php

namespace kareemsliet\Chat\Exceptions;

class UnauthorizedConversationException extends \Exception
{
    public function __construct($message = null, $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message ?? "the conversation is unauthorized.", $code, $previous);
    }
}
