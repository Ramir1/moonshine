<?php

declare(strict_types=1);

namespace MoonShine\Traits\Resource;

use Illuminate\Support\Facades\Gate;
use MoonShine\Exceptions\ResourceException;
use MoonShine\MoonShineAuth;

trait ResourceModelPolicy
{
    protected bool $withPolicy = false;

    /**
     * @return string[]
     */
    public function gateAbilities(): array
    {
        return [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'massDelete',
            'restore',
            'forceDelete',
        ];
    }

    /**
     * @throws ResourceException
     */
    public function can(string $ability): bool
    {
        if (! config('moonshine.auth.enable', true)) {
            return true;
        }

        if (! in_array($ability, $this->gateAbilities())) {
            throw new ResourceException("ability '$ability' not found in the system");
        }

        $user = MoonShineAuth::guard()->user();
        $item = $this->getModel();

        if (! in_array($ability, ['create', 'massDelete'])) {
            $item = $this->getItemOrInstance();
        }

        $checkCustomRules = moonshine()
            ->authorizationRules()
            ->every(fn ($rule) => $rule($this, $user, $ability, $item));

        if (! $checkCustomRules) {
            return false;
        }

        if (! $this->isWithPolicy()) {
            return true;
        }

        return Gate::forUser($user)->allows($ability, $item);
    }

    public function isWithPolicy(): bool
    {
        return $this->withPolicy;
    }
}
