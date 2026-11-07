<?php

namespace App\Exceptions;

use RuntimeException;

/** A permanent business rejection, not a database or queue outage. */
class CouponUsageRejected extends RuntimeException {}
