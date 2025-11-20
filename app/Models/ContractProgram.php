<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractProgram extends Pivot
{
    protected $table = 'contract_program';

    public $incrementing = true;
}
