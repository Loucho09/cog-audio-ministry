<?php

namespace App\Exceptions;

use RuntimeException;

/** A join application cannot be approved or rejected right now. The message is safe to show to staff. */
class JoinApplicationReviewException extends RuntimeException {}
