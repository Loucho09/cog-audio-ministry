<?php

namespace App\Exceptions;

use DomainException;

/** Thrown when a duty roster rule is broken. The message is safe to show to users. */
class RosterRuleViolation extends DomainException {}
