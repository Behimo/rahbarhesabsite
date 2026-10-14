<?php

namespace App\Services;

use App\Models\AccountingLaw;
use App\Models\LawEvolution;

class LawEvolutionService
{
    /** @param  array<string, mixed>  $data */
    public function create(AccountingLaw $law, array $data): LawEvolution
    {
        if (! isset($data['sort_order'])) {
            $data['sort_order'] = (int) $law->evolutions()->max('sort_order') + 1;
        }

        return $law->evolutions()->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(LawEvolution $evolution, array $data): void
    {
        $evolution->update($data);
    }

    public function delete(LawEvolution $evolution): void
    {
        $evolution->delete();
    }
}
