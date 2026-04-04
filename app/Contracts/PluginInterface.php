<?php

namespace App\Contracts;

interface PluginInterface
{
    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get the plugin description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the plugin dependencies.
     *
     * @return array
     */
    public function getDependencies(): array;

    /**
     * Boot the plugin.
     *
     * @return void
     */
    public function boot(): void;
}
