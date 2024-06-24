<?php

namespace Moox\Passkey\Auth;

use App\Models\User;
use Moox\Passkey\Models\Passkey;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $authenticator = Passkey::where(
            'credential_id',
            base64_encode($publicKeyCredentialId)
        )->first();

        if (! $authenticator) {
            return null;
        }

        return PublicKeyCredentialSource::createFromArray($authenticator->public_key);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        // Todo: dynamic user model
        return User::with('authenticators')
            ->where('id', $publicKeyCredentialUserEntity->getId())
            ->first()
            ->authenticators
            ->toArray();
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        // Todo: dynamic user model
        $user = User::where(
            'username',
            $publicKeyCredentialSource->getUserHandle()
        )->firstOrFail();

        // Todo: dynamic user model
        $user->authenticators()->save(new Passkey([
            'credential_id' => $publicKeyCredentialSource->getPublicKeyCredentialId(),
            'public_key' => $publicKeyCredentialSource->jsonSerialize(),
        ]));
    }
}
