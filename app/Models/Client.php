<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Client extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasRelationships;
    protected $fillable = ['name', 'code'];

    // protected $with = ['partnershipContracts'];

    public function clientPics(): HasMany
    {
        return $this->hasMany(ClientPic::class);
    }

    public function partnershipContracts(): HasMany
    {
        return $this->hasMany(PartnershipContract::class);
    }

    // public function programs(): HasManyThrough
    // {
    //     return $this->hasManyThrough(
    //         Program::class,
    //         ContractProgram::class,
    //         'partnership_contract_id',
    //         'id',
    //         'id',
    //         'program_id'
    //     )->join('partnership_contracts', 'partnership_contracts.id', '=', 'contract_program.partnership_contract_id')
    //         ->where('partnership_contracts.client_id', $this->id)
    //         ->select('programs.*');
    // }

    public function programs(): HasManyDeep
    {
        return $this->hasManyDeep(Program::class, [PartnershipContract::class, 'contract_program'])->distinct();
    }
}
