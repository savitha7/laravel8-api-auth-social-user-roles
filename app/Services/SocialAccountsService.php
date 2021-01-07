<?php

namespace App\Services;

use App\Models\User;
use App\Models\SocialAccount;
use Laravel\Socialite\Two\User as ProviderUser;

class SocialAccountsService
{
    /**
     * Find or create user instance by provider user instance and provider name.
     * 
     * @param ProviderUser $providerUser
     * @param string $provider
     * @param array $otherData
     * 
     * @return User
     */
    public function findOrCreate(ProviderUser $providerUser, string $provider, $otherData = []): User
    {
        $linkedSocialAccount = SocialAccount::where('provider_name', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($linkedSocialAccount) {
            return $linkedSocialAccount->user;
        } else {
            $user = null;

            if ($email = $providerUser->getEmail()) {
                $user = User::where('email', $email)->first();
            }

            if (! $user) {
                $user = User::create([
                    'name' => $providerUser->getName(),
                    'email' => $providerUser->getEmail(),
                ]);

                if(isset($otherData['user_role']) && !empty($otherData['user_role'])){ 
                    $user->roles()->attach($otherData['user_role']);
                }
            }

            $user->linkedSocialAccounts()->create([
                'provider_id' => $providerUser->getId(),
                'provider_name' => $provider,
                'name' => $providerUser->getName(),
                'email' => $providerUser->getEmail(),
                'avatar' => $providerUser->getAvatar(),
                'user_id' => $user->id,
            ]);

            return $user;
        }
    }

    /**
     * Find or create user instance by provider user instance and provider name.
     * 
     * @param ProviderUser $providerUser
     * @param string $provider
     * 
     * @return User
     */
    public function isSocialAccountExist(ProviderUser $providerUser, string $provider)
    {
        $linkedSocialAccount = SocialAccount::where('provider_name', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($linkedSocialAccount) {
            return $linkedSocialAccount->user;
        } else {
            $user = null;            
        }

        return $user;
    }
}