<?php

namespace Moox\Passkey\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Moox\Passkey\Auth\CredentialSourceRepository;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;

class LoginController extends Controller
{
    // We use this key across several methods, so we're going to define it here
    const CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY = 'publicKeyCredentialRequestOptions';

    public function generateOptions(Request $request)
    {
        try {
            $user = User::where('username', $request->input('username'))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw ValidationException::withMessages([
                'username' => 'User not found',
            ]);
        }

        // User Entity
        $userEntity = PublicKeyCredentialUserEntity::create(
            $user->username,
            (string) $user->id,
            $user->username,
            null,
        );

        // A repo of our public key credentials
        $pkSourceRepo = new CredentialSourceRepository;

        // A user can have multiple authenticators, so we need to get all of them to check against
        $registeredAuthenticators = $pkSourceRepo->findAllForUserEntity($userEntity);

        // We don’t need the Credential Sources, just the associated Descriptors
        $allowedCredentials = collect($registeredAuthenticators)
            ->pluck('public_key')
            ->map(
                fn ($publicKey) => PublicKeyCredentialSource::createFromArray($publicKey)
            )
            ->map(
                fn (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor => $credential->getPublicKeyCredentialDescriptor()
            )
            ->toArray();

        $pkRequestOptions =
            PublicKeyCredentialRequestOptions::create(
                random_bytes(32) // Challenge
            )
            // Tell the device which authenticators we are allowed to use
                ->allowCredentials(...$allowedCredentials);

        $serializedOptions = $pkRequestOptions->jsonSerialize();

        // It is important to store the the options object in the session
        // for the next step. The data will be needed to check the response from the device.
        $request->session()->put(
            self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY,
            $serializedOptions
        );

        return $serializedOptions;
    }

    public function verify(Request $request, ServerRequestInterface $serverRequest)
    {
        // A repo of our public key credentials
        $pkSourceRepo = new CredentialSourceRepository;

        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());

        $algorithmManager = Manager::create()->add(
            ES256::create(),
            ES256K::create(),
            ES384::create(),
            ES512::create(),
            RS256::create(),
            RS384::create(),
            RS512::create(),
            PS256::create(),
            PS384::create(),
            PS512::create(),
            Ed256::create(),
            Ed512::create(),
        );

        // The validator that will check the response from the device
        $responseValidator = AuthenticatorAssertionResponseValidator::create(
            $pkSourceRepo,
            IgnoreTokenBindingHandler::create(),
            ExtensionOutputCheckerHandler::create(),
            $algorithmManager,
        );

        // A loader that will load the response from the device
        $pkCredentialLoader = PublicKeyCredentialLoader::create(
            AttestationObjectLoader::create($attestationManager)
        );

        $publicKeyCredential = $pkCredentialLoader->load(json_encode($request->all()));

        $authenticatorAssertionResponse = $publicKeyCredential->getResponse();

        if (! $authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            throw ValidationException::withMessages([
                'username' => 'Invalid response type',
            ]);
        }

        // Check the response from the device, this will
        // throw an exception if the response is invalid.
        // For the purposes of this demo, we are letting
        // the exception bubble up so we can see what is
        // going on.
        $publicKeyCredentialSource = $responseValidator->check(
            $publicKeyCredential->getRawId(),
            $authenticatorAssertionResponse,
            PublicKeyCredentialRequestOptions::createFromArray(
                session(self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY)
            ),
            $serverRequest,
            $authenticatorAssertionResponse->getUserHandle(),
        );

        // If we've gotten this far, the response is valid!

        // We don't need the options anymore, so let's remove them from the session
        $request->session()->forget(self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY);

        $user = User::where('username', $publicKeyCredentialSource->getUserHandle())->firstOrFail();

        Auth::login($user);

        return [
            'verified' => true,
        ];
    }
}
