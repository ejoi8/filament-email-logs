<?php

namespace Ejoi8\FilamentEmailLogs\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal tenant model used by the tenancy tests.
 */
class Team extends Model
{
    protected $table = 'teams';

    protected $guarded = [];
}
