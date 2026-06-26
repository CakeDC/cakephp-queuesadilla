<?php

namespace josegonzalez\Queuesadilla\Utility;

trait SettingTrait
{
    /**
     * @var array<string, mixed>
     */
    protected array $settings = [];

    /**
     * @param array<string, mixed>|string|null $key
     */
    public function config(null|string|array $key = null, mixed $value = null): mixed
    {
        if (is_array($key)) {
            $this->settings = array_merge($this->settings, $key);
            $key = null;
        }

        if ($key === null) {
            return $this->settings;
        }

        if ($value === null) {
            if (isset($this->settings[$key])) {
                return $this->settings[$key];
            }

            return null;
        }

        return $this->settings[$key] = $value;
    }

    /**
     * @param array<string, mixed>|string $settings
     */
    public function setting(array|string $settings, string $key, mixed $default = null): mixed
    {
        if (!is_array($settings)) {
            $settings = ['queue' => $settings];
        }

        $settings = array_merge($this->settings, $settings);

        if (isset($settings[$key])) {
            return $settings[$key];
        }

        return $default;
    }
}
