<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use ReflectionMethod;

final class TreeResourcePageExecutor
{
    public function makePage(string $pageClass, object $host): object
    {
        /** @var object $page */
        $page = Livewire::new($pageClass);

        if (property_exists($host, 'lang') && property_exists($page, 'lang')) {
            $page->lang = $host->lang;
        }

        return $page;
    }

    public function mountPageRecord(object $page, Model $record): void
    {
        if (property_exists($page, 'record')) {
            $page->record = $record;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeFill(object $page, array $data): array
    {
        if (! method_exists($page, 'mutateFormDataBeforeFill')) {
            return $data;
        }

        return $this->invokeHook($page, 'mutateFormDataBeforeFill', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeCreate(object $page, array $data): array
    {
        if (! method_exists($page, 'mutateFormDataBeforeCreate')) {
            return $data;
        }

        return $this->invokeHook($page, 'mutateFormDataBeforeCreate', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeSave(object $page, array $data): array
    {
        if (! method_exists($page, 'mutateFormDataBeforeSave')) {
            return $data;
        }

        return $this->invokeHook($page, 'mutateFormDataBeforeSave', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handleRecordCreation(object $page, array $data): Model
    {
        return $this->invokeProtected($page, 'handleRecordCreation', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handleRecordUpdate(object $page, Model $record, array $data): Model
    {
        return $this->invokeProtected($page, 'handleRecordUpdate', $record, $data);
    }

    public function callAfterCreate(object $page): void
    {
        if (! method_exists($page, 'afterCreate')) {
            return;
        }

        $this->invokeHook($page, 'afterCreate');
    }

    public function callAfterSave(object $page): void
    {
        if (! method_exists($page, 'afterSave')) {
            return;
        }

        $this->invokeHook($page, 'afterSave');
    }

    public function invokeProtected(object $object, string $method, mixed ...$arguments): mixed
    {
        return $this->invokeHook($object, $method, ...$arguments);
    }

    public function invokeHook(object $object, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$arguments);
    }
}
