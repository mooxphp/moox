<?php

declare(strict_types=1);

use Moox\Communication\Support\CommunicationValueValidator;

beforeEach(function (): void {
    $this->validator = new CommunicationValueValidator;
});

test('email is stored trimmed and lowercase', function (): void {
    expect($this->validator->canonicalize('email', ' Info@flotlife.com '))
        ->toBe('info@flotlife.com');
});

test('email rejects anything that is not one address', function (): void {
    expect($this->validator->canonicalize('email', "\t"))->toBeNull()
        ->and($this->validator->canonicalize('email', 'info'))->toBeNull()
        ->and($this->validator->canonicalize('email', 'www.pts-jena.de'))->toBeNull()
        ->and($this->validator->canonicalize('email', 'info@a.de; sales@b.de'))->toBeNull();
});

test('phone with a country code is stored as e164', function (): void {
    expect($this->validator->canonicalize('phone', '+49 (89) 8899-0'))
        ->toBe('+498988990')
        ->and($this->validator->canonicalize('fax', '+49 (0)202 748470'))
        ->toBe('+49202748470');
});

test('a national phone number uses the owner calling code', function (): void {
    expect($this->validator->canonicalize('phone', '05257 / 4679', '+49'))
        ->toBe('+4952574679')
        ->and($this->validator->canonicalize('phone', '02246/109221', '49'))
        ->toBe('+492246109221');
});

test('a national phone number without a calling code is rejected', function (): void {
    expect($this->validator->canonicalize('phone', '05257 / 4679'))->toBeNull()
        ->and($this->validator->canonicalize('phone', 'Abwasserzweckverband'))->toBeNull()
        ->and($this->validator->canonicalize('phone', 'office@planufaktur.at'))->toBeNull();
});

test('a website without a scheme is stored as https', function (): void {
    expect($this->validator->canonicalize('url', 'www.atp-polster.de'))
        ->toBe('https://www.atp-polster.de')
        ->and($this->validator->canonicalize('url', 'http://www.dieterbopper.de/'))
        ->toBe('http://www.dieterbopper.de');
});

test('an unknown validation type is rejected', function (): void {
    expect($this->validator->canonicalize('note', 'hello'))->toBeNull();
});
